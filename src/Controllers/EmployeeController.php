<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRequestRepository;
use App\Repositories\PositionRepository;
use App\Services\EmployeeValidator;
use App\Services\PhotoUploader;
use PDOException;
use RuntimeException;

final class EmployeeController extends Controller
{
    private const PER_PAGE = 10;

    private EmployeeRepository $employees;
    private DepartmentRepository $departments;
    private PositionRepository $positions;
    private EmployeeValidator $validator;
    private PhotoUploader $photos;

    public function __construct()
    {
        parent::__construct();
        $this->employees = new EmployeeRepository();
        $this->departments = new DepartmentRepository();
        $this->positions = new PositionRepository();
        $this->validator = new EmployeeValidator($this->employees, $this->departments, $this->positions);
        $this->photos = new PhotoUploader(dirname(__DIR__, 2) . '/public');
    }

    /** GET /employees?q=&department_id=&status=&page= */
    public function index(): void
    {
        $user = $this->requireRole('admin', 'hr', 'head');

        $departmentId = (int) ($_GET['department_id'] ?? 0);
        $status = (string) ($_GET['status'] ?? '');
        $filters = [
            'q'             => trim((string) ($_GET['q'] ?? '')),
            'department_id' => $departmentId > 0 ? (string) $departmentId : '',
            'status'        => in_array($status, ['active', 'on_leave', 'dismissed'], true) ? $status : '',
        ];

        $departments = $this->departments->all();
        // Заведующий видит только свою кафедру — фильтр принудительный, его нельзя подменить в адресе
        if ($user['role'] === 'head') {
            $filters['department_id'] = (string) $user['department_id'];
            $departments = array_values(array_filter($departments, fn ($d) => (int) $d['id'] === (int) $user['department_id']));
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->employees->paginate($filters, $page, self::PER_PAGE);

        $this->render('employees/index', [
            'title'       => 'Сотрудники',
            'active'      => 'employees',
            'employees'   => $result['items'],
            'departments' => $departments,
            'filters'     => $filters,
            'pagination'  => ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total']],
            'canEdit'     => in_array($user['role'], ['admin', 'hr'], true),
        ]);
    }

    /** GET /employees/{id} */
    public function show(int $id): void
    {
        $user = $this->requireRole('admin', 'hr', 'head', 'employee');
        $employee = $this->findOrFail($id);

        $allowed = match ($user['role']) {
            'admin', 'hr' => true,
            'head'        => (int) $employee['department_id'] === (int) $user['department_id'],
            default       => (int) $employee['id'] === (int) $user['employee_id'],   // сотрудник — только себя
        };
        if (!$allowed) {
            $this->abort(403, 'Вы можете просматривать только сотрудников своей кафедры или свой профиль.');
        }

        $leaves = (new LeaveRequestRepository())->list(['employee_id' => $id], 1, 20)['items'];

        $this->render('employees/show', [
            'title'    => $employee['last_name'] . ' ' . $employee['first_name'],
            'active'   => (int) $employee['id'] === (int) $user['employee_id'] ? 'profile' : 'employees',
            'employee' => $employee,
            'leaves'   => $leaves,
            'canEdit'  => in_array($user['role'], ['admin', 'hr'], true),
        ]);
    }

    /** GET /employees/create */
    public function create(): void
    {
        $this->requireRole('admin', 'hr');
        $this->renderForm(['status' => 'active', 'academic_degree' => 'none', 'hire_date' => date('Y-m-d')], [], null);
    }

    /** POST /employees/create */
    public function store(): void
    {
        $this->verifyCsrf();
        $this->requireRole('admin', 'hr');

        [$data, $errors] = $this->validator->validate($_POST, null);
        $photoPath = $errors ? null : $this->savePhoto($errors);
        if ($errors) {
            $this->renderForm($_POST, $errors, null, 422);
            return;
        }

        $id = $this->employees->create($data);
        if ($photoPath !== null) {
            $this->employees->updatePhoto($id, $photoPath);
        }

        $this->flash('success', 'Сотрудник добавлен');
        $this->redirect('/employees/' . $id);
    }

    /** GET /employees/{id}/edit */
    public function edit(int $id): void
    {
        $this->requireRole('admin', 'hr');
        $this->renderForm($this->findOrFail($id), [], $id);
    }

    /** POST /employees/{id}/edit */
    public function update(int $id): void
    {
        $this->verifyCsrf();
        $this->requireRole('admin', 'hr');
        $employee = $this->findOrFail($id);

        [$data, $errors] = $this->validator->validate($_POST, $id);
        $photoPath = $errors ? null : $this->savePhoto($errors);
        if ($errors) {
            // Фото уже сохранённого сотрудника показываем и в форме с ошибками
            $this->renderForm($_POST + ['photo_path' => $employee['photo_path']], $errors, $id, 422);
            return;
        }

        $this->employees->update($id, $data);
        if ($photoPath !== null) {
            $this->employees->updatePhoto($id, $photoPath);
            $this->photos->delete($employee['photo_path']); // старый файл больше не нужен
        }

        $this->flash('success', 'Изменения сохранены');
        $this->redirect('/employees/' . $id);
    }

    /** POST /employees/{id}/delete */
    public function delete(int $id): void
    {
        $this->verifyCsrf();
        $this->requireRole('admin', 'hr');
        $employee = $this->findOrFail($id);

        try {
            $this->employees->delete($id);
        } catch (PDOException $e) {
            // 1451 — нарушение внешнего ключа: на сотрудника ссылаются приказы (ON DELETE RESTRICT)
            if (($e->errorInfo[1] ?? null) === 1451) {
                $this->flash('danger', 'Нельзя удалить сотрудника, по которому есть приказы. Измените статус на «Уволен».');
                $this->redirect('/employees/' . $id);
            }
            throw $e;
        }

        $this->photos->delete($employee['photo_path']);
        $this->flash('success', 'Сотрудник удалён');
        $this->redirect('/employees');
    }

    /** GET /api/employees?q= — JSON для живого поиска */
    public function apiSearch(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->json(['error' => 'Требуется вход в систему'], 401);
        }
        if (!in_array($user['role'], ['admin', 'hr', 'head'], true)) {
            $this->json(['error' => 'Доступ запрещён'], 403);
        }

        // Пустая строка — весь список (как и в заглушке дня 11), но не больше 50 записей
        $q = trim((string) ($_GET['q'] ?? ''));
        $departmentId = $user['role'] === 'head' ? (int) $user['department_id'] : null;
        $rows = $this->employees->search($q, $departmentId, 50);

        // Формат ответа согласован с лекцией 11: массив объектов (app.js рисует из них строки таблицы)
        $data = array_map(fn (array $e) => [
            'id'         => (int) $e['id'],
            'full_name'  => trim("{$e['last_name']} {$e['first_name']} {$e['middle_name']}"),
            'department' => $e['department_name'],
            'position'   => $e['position_title'],
            'phone'      => $e['phone'],
            'hire_date'  => $e['hire_date'],
            'status'     => $e['status'],
        ], $rows);

        $this->json($data);
    }

    // -----------------------------------------------------------------
    // Вспомогательные методы
    // -----------------------------------------------------------------

    private function findOrFail(int $id): array
    {
        $employee = $this->employees->find($id);
        if ($employee === null) {
            $this->abort(404, 'Сотрудник не найден');
        }

        return $employee;
    }

    /** Сохраняет фото, если оно выбрано. При ошибке добавляет её в $errors и возвращает null. */
    private function savePhoto(array &$errors): ?string
    {
        $file = $_FILES['photo'] ?? null;
        if (!PhotoUploader::isProvided($file)) {
            return null;
        }
        try {
            return $this->photos->save($file);
        } catch (RuntimeException $e) {
            $errors['photo'] = $e->getMessage();
            return null;
        }
    }

    private function renderForm(array $employee, array $errors, ?int $id, int $status = 200): void
    {
        $this->render('employees/form', [
            'title'       => $id ? 'Редактирование сотрудника' : 'Новый сотрудник',
            'active'      => 'employees',
            'employee'    => $employee,
            'errors'      => $errors,
            'id'          => $id,
            'departments' => $this->departments->all(),
            'positions'   => $this->positions->all(),
        ], $status);
    }
}
