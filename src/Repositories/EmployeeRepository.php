<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
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

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * Страница списка с поиском и фильтрами.
     * @param array{q?: string, department_id?: int|string, status?: string} $filters
     * @return array{items: array, total: int, page: int, pages: int}
     */
    public function paginate(array $filters, int $page, int $perPage = 10): array
    {
        [$where, $params] = $this->buildWhere($filters);

        // 1) сколько всего записей подходит под фильтр
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM employees e {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // 2) номер страницы не может быть меньше 1 и больше последней
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        // 3) нужная страница: LIMIT — сколько строк, OFFSET — сколько пропустить
        $sql = self::SELECT . " {$where} ORDER BY e.last_name, e.first_name, e.id LIMIT :limit OFFSET :offset";
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
            $params[':q'] = '%' . addcslashes($q, '%_\\') . '%';
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.department_id = :department_id';
            $params[':department_id'] = (int) $filters['department_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'e.status = :status';
            $params[':status'] = (string) $filters['status'];
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
