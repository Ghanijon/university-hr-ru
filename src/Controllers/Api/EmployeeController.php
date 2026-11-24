<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiException;
use App\Core\Validator;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\PositionRepository;
use App\Services\EmployeeValidator;
use App\Services\PhotoUploader;
use App\Services\TransferService;
use PDOException;

final class EmployeeController extends ApiController
{
    private const STATUSES = ['active', 'on_leave', 'dismissed'];

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

    /** GET /api/v1/employees?q=&department_id=1,2&status=active,on_leave&page=&per_page=&sort=-hire_date */
    public function index(): void
    {
        $user = ApiAuth::requireRole('admin', 'hr', 'head');

        $departmentIds = $this->queryList('department_id');
        $statuses = $this->queryList('status');
        $sort = $this->query('sort');

        $errors = [];
        if (array_filter($departmentIds, fn ($id) => !ctype_digit($id) || strlen($id) > 9) !== []) {
            $errors['department_id'] = 'Номера кафедр через запятую';
        }
        if (array_diff($statuses, self::STATUSES) !== []) {
            $errors['status'] = 'Допустимо: ' . implode(', ', self::STATUSES);
        }
        if ($sort !== '' && !isset(EmployeeRepository::SORTS[preg_replace('/^-/', '', $sort)])) {
            $errors['sort'] = 'Допустимо: ' . implode(', ', array_keys(EmployeeRepository::SORTS)) . ' (с минусом — по убыванию)';
        }
        if ($errors) {
            throw ApiException::validation($errors);
        }
        [$page, $perPage] = $this->paging();

        // Заведующий видит только свою кафедру, что бы он ни передал в адресе
        if ($user['role'] === 'head') {
            $departmentIds = [(int) $user['department_id']];
        }

        $filters = ['q' => $this->query('q'), 'department_id' => $departmentIds, 'status' => $statuses];
        $result = $this->employees->paginate($filters, $page, $perPage, $sort, false);

        $this->jsonPage(array_map($this->present(...), $result['items']), $page, $perPage, $result['total']);
    }

    /** GET /api/v1/employees/{id} */
    public function show(int $id): void
    {
        $user = ApiAuth::requireUser();
        $employee = $this->findOrFail($id);

        $allowed = match ($user['role']) {
            'admin', 'hr' => true,
            'head'        => (int) $employee['department_id'] === (int) $user['department_id'],
            default       => (int) $employee['id'] === (int) $user['employee_id'],
        };
        if (!$allowed) {
            throw ApiException::forbidden('Можно смотреть только сотрудников своей кафедры или свой профиль');
        }

        $this->json(['data' => $this->present($employee)]);
    }

    /** POST /api/v1/employees */
    public function store(): void
    {
        ApiAuth::requireRole('admin', 'hr');
        $data = $this->validated(null);

        $id = $this->saveUnique(fn () => $this->employees->create($data));
        header('Location: ' . $this->view->url('/api/v1/employees/' . $id));
        $this->json(['data' => $this->present($this->findOrFail($id))], 201);
    }

    /** PUT /api/v1/employees/{id} — полная замена: не переданные необязательные поля станут пустыми */
    public function update(int $id): void
    {
        ApiAuth::requireRole('admin', 'hr');
        $employee = $this->findOrFail($id);
        $data = $this->validated($id);
        // Иначе заведующий получил бы права на заявки другой кафедры (см. TransferService)
        if ((int) $data['department_id'] !== (int) $employee['department_id'] && $this->employees->isHead($id)) {
            throw ApiException::conflict('Сотрудник заведует кафедрой. Сначала назначьте нового заведующего.');
        }

        $this->saveUnique(fn () => $this->employees->update($id, $data));
        $this->json(['data' => $this->present($this->findOrFail($id))]);
    }

    /** DELETE /api/v1/employees/{id} */
    public function delete(int $id): void
    {
        ApiAuth::requireRole('admin', 'hr');
        $employee = $this->findOrFail($id);

        try {
            $this->employees->delete($id);
        } catch (PDOException $e) {
            // 1451 — на сотрудника ссылаются приказы (ON DELETE RESTRICT). Правило BR-08 (ТЗ, п. 4.2.2)
            if (($e->errorInfo[1] ?? null) === 1451) {
                throw ApiException::conflict('Нельзя удалить сотрудника, по которому есть приказы. Измените статус на «Уволен».');
            }
            throw $e;
        }

        (new PhotoUploader(dirname(__DIR__, 3) . '/public'))->delete($employee['photo_path']);
        $this->noContent();
    }

    /** POST /api/v1/employees/{id}/transfer */
    public function transfer(int $id): void
    {
        $user = ApiAuth::requireRole('admin', 'hr');
        $this->findOrFail($id);

        $input = $this->input('department_id', 'position_id', 'date');
        $v = new Validator($input);
        $v->required('department_id', 'Кафедра')->in('department_id', $this->departments->ids(), 'Кафедра')
          ->required('position_id', 'Должность')->in('position_id', $this->positions->ids(), 'Должность')
          ->required('date', 'Дата перевода')->date('date', 'Дата перевода');
        if ($v->fails()) {
            throw ApiException::validation($v->errors());
        }

        try {
            $order = (new TransferService())->transfer(
                $id,
                (int) $input['department_id'],
                (int) $input['position_id'],
                trim($input['date']),
                (int) $user['id']
            );
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ApiException::conflict('Номер приказа уже занят другим запросом. Перевод отменён, повторите.');
            }
            throw $e;
        }

        $this->json(['data' => ['employee' => $this->present($this->findOrFail($id)), 'order' => $order]]);
    }

    private function findOrFail(int $id): array
    {
        return $this->employees->find($id) ?? throw ApiException::notFound('Сотрудник не найден');
    }

    /** Данные из тела запроса, проверенные теми же правилами, что и веб-форма */
    private function validated(?int $id): array
    {
        $validator = new EmployeeValidator($this->employees, $this->departments, $this->positions);
        [$data, $errors] = $validator->validate($this->input(...EmployeeRepository::FIELDS), $id);
        if ($errors) {
            throw ApiException::validation($errors);
        }

        return $data;
    }

    /** 1062 — нарушен UNIQUE (email): такой e-mail успел занять параллельный запрос */
    private function saveUnique(callable $save): mixed
    {
        try {
            return $save();
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw ApiException::conflict('Сотрудник с таким e-mail уже есть');
            }
            throw $e;
        }
    }

    /** Строка БД → объект Employee из openapi.yaml */
    private function present(array $e): array
    {
        return [
            'id'              => (int) $e['id'],
            'last_name'       => $e['last_name'],
            'first_name'      => $e['first_name'],
            'middle_name'     => $e['middle_name'],
            'birth_date'      => $e['birth_date'],
            'gender'          => $e['gender'],
            'phone'           => $e['phone'],
            'email'           => $e['email'],
            'department_id'   => (int) $e['department_id'],
            'department_name' => $e['department_name'],
            'position_id'     => (int) $e['position_id'],
            'position_title'  => $e['position_title'],
            'academic_degree' => $e['academic_degree'],
            'hire_date'       => $e['hire_date'],
            'status'          => $e['status'],
        ];
    }
}
