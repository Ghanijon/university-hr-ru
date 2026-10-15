<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Throwable;

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

    /**
     * Список заявок с фильтрами и пагинацией.
     * @param array{employee_id?: int, department_id?: int, status?: string} $filters
     */
    public function list(array $filters, int $page = 1, int $perPage = 10): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM leave_requests lr JOIN employees e ON e.id = lr.employee_id {$where}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        // Сначала заявки, ожидающие решения, затем — по дате подачи (новые сверху)
        $sql = self::SELECT . " {$where}
                ORDER BY FIELD(lr.status, 'pending', 'head_approved', 'approved', 'rejected'), lr.created_at DESC, lr.id DESC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $stmt->execute();

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    private function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];
        if (!empty($filters['employee_id'])) {
            $conditions[] = 'lr.employee_id = :employee_id';
            $params[':employee_id'] = (int) $filters['employee_id'];
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = 'e.department_id = :department_id';
            $params[':department_id'] = (int) $filters['department_id'];
        }
        if (!empty($filters['status'])) {
            $conditions[] = 'lr.status = :status';
            $params[':status'] = (string) $filters['status'];
        }

        return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
    }

    /** Последние заявки (для главной страницы) с теми же фильтрами, что и list() */
    public function latest(int $limit = 5, array $filters = []): array
    {
        [$where, $params] = $this->buildWhere($filters);
        $stmt = $this->db->prepare(self::SELECT . " {$where} ORDER BY lr.created_at DESC, lr.id DESC LIMIT :limit");
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Количество заявок в статусе (с фильтрами по сотруднику / кафедре) */
    public function countByStatus(string $status, array $filters = []): int
    {
        [$where, $params] = $this->buildWhere(['status' => $status] + $filters);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM leave_requests lr JOIN employees e ON e.id = lr.employee_id {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE lr.id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO leave_requests (employee_id, type, start_date, end_date, reason)
            VALUES (:employee_id, :type, :start_date, :end_date, :reason)');
        $stmt->execute([
            ':employee_id' => $data['employee_id'],
            ':type'        => $data['type'],
            ':start_date'  => $data['start_date'],
            ':end_date'    => $data['end_date'],
            ':reason'      => $data['reason'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Пересекается ли период с другой активной (не отклонённой) заявкой сотрудника */
    public function hasOverlap(int $employeeId, string $start, string $end): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM leave_requests
            WHERE employee_id = :employee_id
              AND status <> 'rejected'
              AND start_date <= :end_date
              AND end_date   >= :start_date");
        $stmt->execute([':employee_id' => $employeeId, ':start_date' => $start, ':end_date' => $end]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Решение заведующего (1-й уровень).
     * Условие «AND status = 'pending'» защищает от двойного нажатия и гонки:
     * если кто-то уже изменил заявку, UPDATE не затронет ни одной строки.
     */
    public function setHeadDecision(int $id, string $status, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE leave_requests SET status = :status, head_decision_by = :user_id
            WHERE id = :id AND status = 'pending'");
        $stmt->execute([':status' => $status, ':user_id' => $userId, ':id' => $id]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Решение отдела кадров (2-й уровень). При утверждении в ТОЙ ЖЕ транзакции
     * создаётся приказ об отпуске: либо выполнятся оба действия, либо ни одного.
     */
    public function setHrDecision(int $id, string $status, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE leave_requests SET status = :status, hr_decision_by = :user_id
                WHERE id = :id AND status = 'head_approved'");
            $stmt->execute([':status' => $status, ':user_id' => $userId, ':id' => $id]);
            if ($stmt->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }

            if ($status === 'approved') {
                $this->createLeaveOrder($id, $userId);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Приказ «ОТ-2026-0015» по заявке № 15 */
    private function createLeaveOrder(int $leaveId, int $userId): void
    {
        $leave = $this->find($leaveId);
        $types = ['annual' => 'ежегодный отпуск', 'study' => 'учебный отпуск',
                  'unpaid' => 'отпуск без сохранения заработной платы', 'sick' => 'отпуск по болезни'];
        // «Рахимов Ф. Д.» — фамилия и инициалы
        $shortName = $leave['last_name'] . ' ' . mb_substr($leave['first_name'], 0, 1) . '.'
            . ($leave['middle_name'] ? ' ' . mb_substr($leave['middle_name'], 0, 1) . '.' : '');
        $text = sprintf(
            'Предоставить %s %s с %s по %s.',
            $shortName,
            $types[$leave['type']],
            date('d.m.Y', strtotime($leave['start_date'])),
            date('d.m.Y', strtotime($leave['end_date']))
        );

        $stmt = $this->db->prepare("
            INSERT INTO orders (number, type, employee_id, order_date, text, created_by)
            VALUES (:number, 'leave', :employee_id, CURDATE(), :text, :created_by)");
        $stmt->execute([
            ':number'      => sprintf('ОТ-%s-%04d', date('Y'), $leaveId),
            ':employee_id' => $leave['employee_id'],
            ':text'        => $text,
            ':created_by'  => $userId,
        ]);
    }
}
