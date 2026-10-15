<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRequestRepository;
use App\Services\LeaveRequestRules;

final class LeaveController extends Controller
{
    private const STATUSES = ['pending', 'head_approved', 'approved', 'rejected'];

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
        $filters = $this->visibilityFilter($user);
        if (in_array($status, self::STATUSES, true)) {
            $filters['status'] = $status;
        }

        $result = $this->leaves->list($filters, max(1, (int) ($_GET['page'] ?? 1)));

        // Счётчики для вкладок-фильтров
        $counts = [];
        foreach (self::STATUSES as $s) {
            $counts[$s] = $this->leaves->countByStatus($s, $this->visibilityFilter($user));
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

        // Сотрудник и заведующий подают заявку только за себя; ОК и администратор — за любого сотрудника
        $onBehalf = in_array($user['role'], ['admin', 'hr'], true);
        $employeeId = $onBehalf ? (int) ($_POST['employee_id'] ?? 0) : (int) $user['employee_id'];

        $v = new Validator($_POST);
        $v->required('type', 'Тип отпуска')->in('type', LeaveRequestRules::TYPES, 'Тип отпуска')
          ->required('start_date', 'Дата начала')
          ->required('end_date', 'Дата окончания')
          ->maxLength('reason', 255, 'Причина');
        $errors = $v->errors();

        $employee = $employeeId > 0 ? (new EmployeeRepository())->find($employeeId) : null;
        if ($employee === null) {
            $errors['employee_id'] = 'Выберите сотрудника';
        } elseif ($employee['status'] === 'dismissed') {
            $errors['employee_id'] = 'Уволенный сотрудник не может подать заявку';
        }

        $data = [
            'employee_id' => $employeeId,
            'type'        => (string) ($_POST['type'] ?? ''),
            'start_date'  => trim((string) ($_POST['start_date'] ?? '')),
            'end_date'    => trim((string) ($_POST['end_date'] ?? '')),
            'reason'      => trim((string) ($_POST['reason'] ?? '')) ?: null,
        ];

        // Бизнес-правила дат — в отдельном тестируемом классе
        if (!isset($errors['start_date']) && !isset($errors['end_date'])) {
            $errors += LeaveRequestRules::validateDates($data['start_date'], $data['end_date'], $data['type']);
        }
        if (!$errors && $this->leaves->hasOverlap($employeeId, $data['start_date'], $data['end_date'])) {
            $errors['start_date'] = 'На эти даты у сотрудника уже есть заявка';
        }

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
            ? $this->leaves->setHeadDecision($id, $next, (int) $user['id'])
            : $this->leaves->setHrDecision($id, $next, (int) $user['id']);

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

    /** Какие заявки видит пользователь: сотрудник — свои, заведующий — своей кафедры, ОК и админ — все */
    private function visibilityFilter(array $user): array
    {
        return match ($user['role']) {
            'employee' => ['employee_id' => (int) $user['employee_id']],
            'head'     => ['department_id' => (int) $user['department_id']],
            default    => [],
        };
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
