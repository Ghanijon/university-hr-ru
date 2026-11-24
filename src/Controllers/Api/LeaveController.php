<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiException;
use App\Core\Validator;
use App\Repositories\LeaveRequestRepository;
use App\Services\LeaveRequestRules;
use App\Services\LeaveRequestValidator;

final class LeaveController extends ApiController
{
    private LeaveRequestRepository $leaves;

    public function __construct()
    {
        parent::__construct();
        $this->leaves = new LeaveRequestRepository();
    }

    /** GET /api/v1/leaves?status=&page=&per_page= */
    public function index(): void
    {
        $user = ApiAuth::requireUser();
        $filters = LeaveRequestRules::visibilityFilter($user);

        $status = $this->query('status');
        if ($status !== '') {
            if (!in_array($status, LeaveRequestRules::STATUSES, true)) {
                throw ApiException::validation(['status' => 'Допустимо: ' . implode(', ', LeaveRequestRules::STATUSES)]);
            }
            $filters['status'] = $status;
        }
        [$page, $perPage] = $this->paging();

        $result = $this->leaves->list($filters, $page, $perPage, false);
        $this->jsonPage(array_map($this->present(...), $result['items']), $page, $perPage, $result['total']);
    }

    /** POST /api/v1/leaves */
    public function store(): void
    {
        $user = ApiAuth::requireUser();
        $input = $this->input('employee_id', 'type', 'start_date', 'end_date', 'reason');

        [$data, $errors] = (new LeaveRequestValidator($this->leaves))->validate($input, $user);
        if ($errors) {
            throw ApiException::validation($errors);
        }

        $id = $this->leaves->create($data);
        $this->json(['data' => $this->present($this->leaves->find($id))], 201);
    }

    /** POST /api/v1/leaves/{id}/approve */
    public function approve(int $id): void
    {
        $this->decide($id, 'approve');
    }

    /** POST /api/v1/leaves/{id}/reject */
    public function reject(int $id): void
    {
        $this->decide($id, 'reject');
    }

    private function decide(int $id, string $action): void
    {
        $user = ApiAuth::requireRole('admin', 'hr', 'head');

        // expected_status — статус, который клиент видел на экране (необязательно)
        $input = $this->input('expected_status');
        $v = (new Validator($input))->in('expected_status', LeaveRequestRules::STATUSES, 'Ожидаемый статус');
        if ($v->fails()) {
            throw ApiException::validation($v->errors());
        }

        $leave = $this->leaves->find($id) ?? throw ApiException::notFound('Заявка не найдена');
        $expected = trim($input['expected_status']) ?: $leave['status'];

        $reason = LeaveRequestRules::denialReason($leave, $user, $action);
        if ($reason === 'forbidden') {
            throw ApiException::forbidden('Это действие с заявкой вам недоступно');
        }
        if ($reason === 'conflict' || $expected !== $leave['status']) {
            throw ApiException::conflict("Решение по заявке уже принято, её статус: {$leave['status']}");
        }

        $next = LeaveRequestRules::nextStatus($leave['status'], $action, $user['role']);
        $saved = $leave['status'] === 'pending'
            ? $this->leaves->setHeadDecision($id, $expected, $next, (int) $user['id'])
            : $this->leaves->setHrDecision($id, $expected, $next, (int) $user['id']);
        if (!$saved) {
            // Между find() и UPDATE заявку успел изменить другой пользователь
            throw ApiException::conflict('Заявку уже изменил другой пользователь. Обновите список.');
        }

        $this->json(['data' => $this->present($this->leaves->find($id))]);
    }

    /** Строка БД → объект LeaveRequest из openapi.yaml */
    private function present(array $l): array
    {
        return [
            'id'               => (int) $l['id'],
            'employee_id'      => (int) $l['employee_id'],
            'employee_name'    => trim("{$l['last_name']} {$l['first_name']} {$l['middle_name']}"),
            'department_id'    => (int) $l['department_id'],
            'department_name'  => $l['department_name'],
            'type'             => $l['type'],
            'start_date'       => $l['start_date'],
            'end_date'         => $l['end_date'],
            'days'             => (int) $l['days'],
            'reason'           => $l['reason'],
            'status'           => $l['status'],
            'head_decision_by' => $l['head_decision_by'],
            'hr_decision_by'   => $l['hr_decision_by'],
            'created_at'       => $l['created_at'],
        ];
    }
}
