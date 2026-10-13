<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DepartmentRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** Все кафедры с факультетом, заведующим и числом сотрудников (без уволенных) */
    public function all(): array
    {
        $sql = "
            SELECT d.id, d.name, d.faculty_id, f.name AS faculty_name,
                   CONCAT_WS(' ', h.last_name, h.first_name, h.middle_name) AS head_name,
                   (SELECT COUNT(*) FROM employees e
                     WHERE e.department_id = d.id AND e.status <> 'dismissed') AS employees_count
            FROM departments d
            JOIN faculties f      ON f.id = d.faculty_id
            LEFT JOIN employees h ON h.id = d.head_employee_id
            ORDER BY f.name, d.name";

        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM departments WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** Список id — для проверки значения из формы */
    public function ids(): array
    {
        return array_map('intval', $this->db->query('SELECT id FROM departments')->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Отчёт «штат по кафедрам». Условная агрегация: SUM(условие) считает строки,
     * где условие истинно (в MySQL TRUE = 1).
     */
    public function staffReport(): array
    {
        $sql = "
            SELECT f.name AS faculty_name, d.id, d.name AS department_name,
                   COUNT(e.id)                                  AS total,
                   COALESCE(SUM(p.category = 'academic'), 0)    AS academic,
                   COALESCE(SUM(e.academic_degree <> 'none'), 0) AS with_degree,
                   COALESCE(SUM(e.status = 'on_leave'), 0)      AS on_leave
            FROM departments d
            JOIN faculties f      ON f.id = d.faculty_id
            LEFT JOIN employees e ON e.department_id = d.id AND e.status <> 'dismissed'
            LEFT JOIN positions p ON p.id = e.position_id
            GROUP BY f.name, d.id, d.name
            ORDER BY f.name, d.name";

        return $this->db->query($sql)->fetchAll();
    }
}
