<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class OrderRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /** Следующий номер в году: «ПЕ-2026-0007». От совпадения при гонке защищает UNIQUE (number). */
    public function nextNumber(string $prefix, string $year): string
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(number, '-', -1) AS UNSIGNED)), 0) + 1
            FROM orders WHERE number LIKE :pattern");
        $stmt->execute([':pattern' => "{$prefix}-{$year}-%"]);

        return sprintf('%s-%s-%04d', $prefix, $year, (int) $stmt->fetchColumn());
    }

    /** @return int id приказа */
    public function create(string $number, string $type, int $employeeId, string $date, string $text, int $userId): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO orders (number, type, employee_id, order_date, text, created_by)
            VALUES (:number, :type, :employee_id, :order_date, :text, :created_by)');
        $stmt->execute([
            ':number'      => $number,
            ':type'        => $type,
            ':employee_id' => $employeeId,
            ':order_date'  => $date,
            ':text'        => $text,
            ':created_by'  => $userId,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
