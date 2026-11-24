<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRequestRepository;
use App\Services\LeaveRequestRules;
use App\Services\LeaveRequestValidator;

final class LeaveController extends Controller
{
    private LeaveRequestRepository $leaves;

    public function __construct()
    {
        parent::__construct();
        $this->leaves = new LeaveRequestRepository();
    }

    /** GET /leaves?status=&page= */
    public function index(): void
    {
        $user = $this->requireRole('admin', 'hr', 'head', 'employee');

        $status = (string) ($_GET['status'] ?? '');
        $filters = LeaveRequestRules::visibilityFilter($user);
        if (in_array($status, LeaveRequestRules::STATUSES, true)) {
            $filters['status'] = $status;
        }

        $result = $this->leaves->list($filters, max(1, (int) ($_GET['page'] ?? 1)));

        // Счётчики для вкладок-фильтров
        $counts = [];
        foreach (LeaveRequestRules::STATUSES as $s) {
            $counts[$s] = $this->leaves->countByStatus($s, LeaveRequestRules::visibilityFilter($user));
        }

        $this->render('leaves/index', [
            'title'      => 'Заявки на отпуск',
            'active'     => 'leaves',
            'leaves'     => $result['items'],
            'status'     => $filters['status'] ?? '',
            'counts'     => $counts,
            'user'       => $user,
            'pagination' => ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total']],
        ]);
    }

    /** GET /leaves/create */
    public function create(): void
    {
        $user = $this->requireRole('admin', 'hr', 'head', 'employee');
        $this->renderForm($user, ['type' => 'annual'], []);
    }

    /** POST /leaves/create */
    public function store(): void
    {
        $this->verifyCsrf();
        $user = $this->requireRole('admin', 'hr', 'head', 'employee');

        [$data, $errors] = (new LeaveRequestValidator($this->leaves))->validate($_POST, $user);
        if ($errors) {
            $this->renderForm($user, $_POST, $errors, 422);
            return;
        }

        $this->leaves->create($data);
        $this->flash('success', 'Заявка отправлена на согласование');
        $this->redirect('/leaves');
    }

    /** POST /leaves/{id}/approve */
    public function approve(int $id): void
    {
        $this->decide($id, 'approve');
    }

    /** POST /leaves/{id}/reject */
    public function reject(int $id): void
    {
        $this->decide($id, 'reject');
    }

    /** Общая логика согласования и отклонения */
    private function decide(int $id, string $action): void
    {
        $this->verifyCsrf();
        $user = $this->requireRole('admin', 'hr', 'head');

        $leave = $this->leaves->find($id);
        if ($leave === null) {
            $this->abort(404, 'Заявка не найдена');
        }

        if (!LeaveRequestRules::canDecide($leave, $user, $action)) {
            $this->flash('danger', 'Это действие с заявкой № ' . $id . ' вам недоступно.');
            $this->redirect('/leaves');
        }

        $next = LeaveRequestRules::nextStatus($leave['status'], $action, $user['role']);
        $saved = $leave['status'] === 'pending'
            ? $this->leaves->setHeadDecision($id, $leave['status'], $next, (int) $user['id'])
            : $this->leaves->setHrDecision($id, $leave['status'], $next, (int) $user['id']);

        if (!$saved) {
            $this->flash('warning', 'Заявку уже изменил другой пользователь. Обновите список.');
        } else {
            $messages = [
                'head_approved' => 'Заявка согласована и передана в отдел кадров',
                'approved'      => 'Заявка утверждена, приказ об отпуске создан',
                'rejected'      => 'Заявка отклонена',
            ];
            $this->flash('success', $messages[$next]);
        }
        $this->redirect('/leaves');
    }

    private function renderForm(array $user, array $values, array $errors, int $status = 200): void
    {
        $onBehalf = in_array($user['role'], ['admin', 'hr'], true);

        $this->render('leaves/form', [
            'title'     => 'Новая заявка на отпуск',
            'active'    => 'leaves',
            'values'    => $values,
            'errors'    => $errors,
            'onBehalf'  => $onBehalf,
            'employees' => $onBehalf ? (new EmployeeRepository())->listForSelect() : [],
            'user'      => $user,
        ], $status);
    }
}
