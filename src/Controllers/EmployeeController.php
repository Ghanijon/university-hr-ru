<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Validator;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\PositionRepository;
use PDOException;

final class EmployeeController extends Controller
{
    private const PER_PAGE = 10;

    /** Тексты сообщений после редиректа (?msg=created) */
    private const MESSAGES = [
        'created'    => ['success', 'Сотрудник добавлен'],
        'updated'    => ['success', 'Изменения сохранены'],
        'deleted'    => ['success', 'Сотрудник удалён'],
        'has_orders' => ['danger', 'Нельзя удалить сотрудника, по которому есть приказы. Измените статус на «Уволен».'],
    ];

    private EmployeeRepository $employees;
    private DepartmentRepository $departments;
    private PositionRepository $positions;

    public function __construct()
    {
        parent::__construct();
        $this->employees = new EmployeeRepository();
        $this->departments = new DepartmentRepository();
        $this->positions = new PositionRepository();
    }

    /** GET /employees?q=&department_id=&status=&page= */
    public function index(): void
    {
        $departmentId = (int) ($_GET['department_id'] ?? 0);
        $status = (string) ($_GET['status'] ?? '');
        $filters = [
            'q'             => trim((string) ($_GET['q'] ?? '')),
            'department_id' => $departmentId > 0 ? (string) $departmentId : '',
            'status'        => in_array($status, ['active', 'on_leave', 'dismissed'], true) ? $status : '',
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $result = $this->employees->paginate($filters, $page, self::PER_PAGE);

        $this->render('employees/index', [
            'title'       => 'Сотрудники',
            'active'      => 'employees',
            'employees'   => $result['items'],
            'departments' => $this->departments->all(),
            'filters'     => $filters,
            'pagination'  => ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total']],
            'canEdit'     => true,
            'notice'      => self::MESSAGES[$_GET['msg'] ?? ''] ?? null,
        ]);
    }

    /** GET /employees/{id} */
    public function show(int $id): void
    {
        $employee = $this->findOrFail($id);

        $this->render('employees/show', [
            'title'    => $employee['last_name'] . ' ' . $employee['first_name'],
            'active'   => 'employees',
            'employee' => $employee,
            'canEdit'  => true,
            'notice'   => self::MESSAGES[$_GET['msg'] ?? ''] ?? null,
        ]);
    }

    /** GET /employees/create */
    public function create(): void
    {
        $this->renderForm([
            'status' => 'active', 'academic_degree' => 'none', 'hire_date' => date('Y-m-d'),
        ], [], null);
    }

    /** POST /employees/create */
    public function store(): void
    {
        [$data, $errors] = $this->validate($_POST, null);
        if ($errors) {
            $this->renderForm($_POST, $errors, null, 422);
            return;
        }

        $id = $this->employees->create($data);
        $this->redirect('/employees/' . $id . '?msg=created');
    }

    /** GET /employees/{id}/edit */
    public function edit(int $id): void
    {
        $employee = $this->findOrFail($id);
        $this->renderForm($employee, [], $id);
    }

    /** POST /employees/{id}/edit */
    public function update(int $id): void
    {
        $this->findOrFail($id);

        [$data, $errors] = $this->validate($_POST, $id);
        if ($errors) {
            $this->renderForm($_POST, $errors, $id, 422);
            return;
        }

        $this->employees->update($id, $data);
        $this->redirect('/employees/' . $id . '?msg=updated');
    }

    /** POST /employees/{id}/delete */
    public function delete(int $id): void
    {
        $this->findOrFail($id);

        try {
            $this->employees->delete($id);
        } catch (PDOException $e) {
            // 1451 — нарушение внешнего ключа: на сотрудника ссылаются приказы (ON DELETE RESTRICT)
            if (($e->errorInfo[1] ?? null) === 1451) {
                $this->redirect('/employees/' . $id . '?msg=has_orders');
            }
            throw $e;
        }

        $this->redirect('/employees?msg=deleted');
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

    /**
     * Проверяет данные формы.
     * @return array{0: array, 1: array<string, string>} [очищенные данные, ошибки]
     */
    private function validate(array $input, ?int $id): array
    {
        $v = new Validator($input);
        $v->required('last_name', 'Фамилия')->maxLength('last_name', 60, 'Фамилия')
          ->required('first_name', 'Имя')->maxLength('first_name', 60, 'Имя')
          ->maxLength('middle_name', 60, 'Отчество')
          ->required('birth_date', 'Дата рождения')->date('birth_date', 'Дата рождения')
          ->required('gender', 'Пол')->in('gender', ['m', 'f'], 'Пол')
          ->maxLength('phone', 20, 'Телефон')
          ->pattern('phone', '/^[0-9+()\s\-]{5,20}$/', 'Телефон: только цифры, пробелы, +, -, скобки')
          ->maxLength('email', 120, 'E-mail')->email('email', 'E-mail')
          ->required('department_id', 'Кафедра')->in('department_id', $this->departments->ids(), 'Кафедра')
          ->required('position_id', 'Должность')->in('position_id', $this->positions->ids(), 'Должность')
          ->required('academic_degree', 'Учёная степень')
          ->in('academic_degree', ['none', 'candidate', 'doctor', 'phd'], 'Учёная степень')
          ->required('hire_date', 'Дата приёма')->date('hire_date', 'Дата приёма')
          ->required('status', 'Статус')->in('status', ['active', 'on_leave', 'dismissed'], 'Статус');

        // Приводим данные к виду для БД: обрезаем пробелы, пустые необязательные поля → NULL
        $data = [];
        foreach (EmployeeRepository::FIELDS as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            $data[$field] = $value === '' ? null : $value;
        }

        // Правила, которые зависят от других данных
        $errors = $v->errors();
        $today = date('Y-m-d');
        if (!isset($errors['birth_date']) && $data['birth_date'] > date('Y-m-d', strtotime('-18 years'))) {
            $v->addError('birth_date', 'Сотруднику должно быть не меньше 18 лет');
        }
        if (!isset($errors['hire_date']) && $data['hire_date'] > $today) {
            $v->addError('hire_date', 'Дата приёма не может быть в будущем');
        }
        if (!isset($errors['email']) && $data['email'] !== null && $this->employees->emailExists($data['email'], $id)) {
            $v->addError('email', 'Сотрудник с таким e-mail уже есть');
        }

        return [$data, $v->errors()];
    }
}
