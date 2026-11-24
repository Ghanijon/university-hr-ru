<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Sql;
use PDO;

/**
 * Все SQL-запросы к таблице employees.
 * Значения из запроса пользователя попадают в SQL ТОЛЬКО через параметры (:name),
 * никогда через конкатенацию строк — это защита от SQL-инъекций.
 */
final class EmployeeRepository
{
    /** Поля, которые можно сохранять из формы */
    public const FIELDS = [
        'last_name', 'first_name', 'middle_name', 'birth_date', 'gender', 'phone', 'email',
        'department_id', 'position_id', 'academic_degree', 'hire_date', 'status',
    ];

    /** Общая часть SELECT: сотрудник + кафедра + факультет + должность (JOIN четырёх таблиц) */
    private const SELECT = '
        SELECT e.*,
               d.name     AS department_name,
               f.name     AS faculty_name,
               p.title    AS position_title,
               p.category AS position_category
        FROM employees e
        JOIN departments d ON d.id = e.department_id
        JOIN faculties   f ON f.id = d.faculty_id
        JOIN positions   p ON p.id = e.position_id';

    /**
     * Белый список сортировки: что можно написать в ?sort= → выражение SQL.
     * ORDER BY нельзя передать параметром: подготовленный запрос подставляет ЗНАЧЕНИЯ,
     * а имя столбца — часть текста SQL. ORDER BY :sort сортировал бы по строке-константе
     * 'hire_date', то есть никак. Поэтому берём готовый SQL из этого списка.
     */
    public const SORTS = [
        'last_name'  => 'e.last_name',
        'hire_date'  => 'e.hire_date',
        'birth_date' => 'e.birth_date',
        'department' => 'd.name',
        'position'   => 'p.title',
        'status'     => 'e.status',
        'id'         => 'e.id',
    ];

    private const DEFAULT_ORDER = 'e.last_name, e.first_name, e.id';

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * Страница списка с поиском и фильтрами.
     * $sort — поле из SORTS, с минусом по убыванию («-hire_date»).
     * $clamp — номер больше последней страницы заменить последней (веб); API вернёт пустую страницу.
     * @param array{q?: string, department_id?: int|string|int[]|null, status?: string|string[]} $filters
     * @return array{items: array, total: int, page: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage = 10, string $sort = '', bool $clamp = true): array
    {
        [$where, $params] = $this->buildWhere($filters);

        // 1) сколько всего записей подходит под фильтр
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM employees e {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // 2) номер страницы не может быть меньше 1 и больше последней
        $pages = max(1, (int) ceil($total / $perPage));
        $page = $clamp ? min(max(1, $page), $pages) : max(1, $page);

        // 3) нужная страница: LIMIT — сколько строк, OFFSET — сколько пропустить
        $order = Sql::orderBy($sort, self::SORTS, self::DEFAULT_ORDER);
        $sql = self::SELECT . " {$where} ORDER BY {$order} LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** Собирает WHERE из фильтров. Возвращает [строка SQL, параметры]. */
    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            // Ищем по ФИО целиком: «Рахимов Фар» тоже найдётся
            $conditions[] = "CONCAT_WS(' ', e.last_name, e.first_name, e.middle_name) LIKE :q";
            $params[':q'] = '%' . Sql::escapeLike($q) . '%';
        }
        // null, '' и [] — «все»; любое другое значение (даже 0) — фильтр. Массив → IN (...)
        foreach (['department_id', 'status'] as $column) {
            $value = $filters[$column] ?? '';
            if ($value === '' || $value === []) {
                continue;
            }
            $values = array_values((array) $value);
            if ($column === 'department_id') {
                $values = array_map('intval', $values);
            }
            [$placeholders, $inParams] = Sql::in($column, $values);
            $conditions[] = "e.{$column} IN ({$placeholders})";   // $column — из списка выше, не от пользователя
            $params += $inParams;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE e.id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** @return int id новой записи */
    public function create(array $data): int
    {
        $columns = implode(', ', self::FIELDS);
        $placeholders = ':' . implode(', :', self::FIELDS);

        $stmt = $this->db->prepare("INSERT INTO employees ({$columns}) VALUES ({$placeholders})");
        $stmt->execute($this->onlyFields($data));

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        // «last_name = :last_name, first_name = :first_name, ...»
        $set = implode(', ', array_map(fn ($f) => "{$f} = :{$f}", self::FIELDS));

        $stmt = $this->db->prepare("UPDATE employees SET {$set} WHERE id = :id");
        $stmt->execute($this->onlyFields($data) + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM employees WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * Короткий поиск для API живого поиска (до $limit записей).
     * $departmentId — ограничение для заведующего кафедрой.
     */
    public function search(string $q, ?int $departmentId = null, int $limit = 10): array
    {
        [$where, $params] = $this->buildWhere(['q' => $q, 'department_id' => $departmentId]);
        $stmt = $this->db->prepare(self::SELECT . " {$where} ORDER BY e.last_name, e.first_name LIMIT :limit");
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Работающие сотрудники для выпадающего списка (форма заявки у отдела кадров) */
    public function listForSelect(): array
    {
        return $this->db->query("
            SELECT id, CONCAT_WS(' ', last_name, first_name, middle_name) AS full_name
            FROM employees WHERE status <> 'dismissed'
            ORDER BY last_name, first_name")->fetchAll();
    }

    /** Заведует ли сотрудник какой-либо кафедрой */
    public function isHead(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM departments WHERE head_employee_id = :id');
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Строка сотрудника для изменения. FOR UPDATE блокирует строку до конца транзакции */
    public function findForUpdate(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM employees WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function setPlace(int $id, int $departmentId, int $positionId): void
    {
        $stmt = $this->db->prepare('
            UPDATE employees SET department_id = :department_id, position_id = :position_id WHERE id = :id');
        $stmt->execute([':department_id' => $departmentId, ':position_id' => $positionId, ':id' => $id]);
    }

    public function updatePhoto(int $id, ?string $path): void
    {
        $stmt = $this->db->prepare('UPDATE employees SET photo_path = :path WHERE id = :id');
        $stmt->execute([':path' => $path, ':id' => $id]);
    }

    /** Есть ли уже сотрудник с таким e-mail (кроме сотрудника $exceptId при редактировании) */
    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM employees WHERE email = :email AND id <> :id');
        $stmt->execute([':email' => $email, ':id' => $exceptId ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /** Количество сотрудников по статусам: ['active' => 41, 'on_leave' => 2, ...] */
    public function countByStatus(?int $departmentId = null): array
    {
        $sql = 'SELECT status, COUNT(*) AS cnt FROM employees';
        $params = [];
        if ($departmentId !== null) {
            $sql .= ' WHERE department_id = :department_id';
            $params[':department_id'] = $departmentId;
        }
        $stmt = $this->db->prepare($sql . ' GROUP BY status');
        $stmt->execute($params);

        $result = ['active' => 0, 'on_leave' => 0, 'dismissed' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }

        return $result;
    }

    /** Оставляет в массиве только разрешённые поля (защита от лишних полей формы) */
    private function onlyFields(array $data): array
    {
        $result = [];
        foreach (self::FIELDS as $field) {
            $result[$field] = $data[$field] ?? null;
        }

        return $result;
    }
}
