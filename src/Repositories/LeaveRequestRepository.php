<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LeaveRequestRepository
{
    private const SELECT = "
        SELECT lr.*,
               DATEDIFF(lr.end_date, lr.start_date) + 1 AS days,
               e.last_name, e.first_name, e.middle_name, e.department_id,
               d.name AS department_name
        FROM leave_requests lr
        JOIN employees   e ON e.id = lr.employee_id
        JOIN departments d ON d.id = e.department_id";

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** Последние заявки (для главной страницы) */
    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare(self::SELECT . ' ORDER BY lr.created_at DESC, lr.id DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Количество заявок в статусе */
    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM leave_requests WHERE status = :status');
        $stmt->execute([':status' => $status]);

        return (int) $stmt->fetchColumn();
    }
}
