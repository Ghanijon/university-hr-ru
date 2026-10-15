<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    /** Пользователь + данные связанного сотрудника (кафедра нужна для прав заведующего) */
    private const SELECT = "
        SELECT u.id, u.username, u.password_hash, u.role, u.is_active, u.employee_id, u.last_login_at,
               e.department_id,
               CONCAT_WS(' ', e.last_name, e.first_name) AS full_name
        FROM users u
        LEFT JOIN employees e ON e.id = u.employee_id";

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE u.username = :username');
        $stmt->execute([':username' => $username]);

        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE u.id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }
        unset($user['password_hash']); // хеш пароля за пределами входа не нужен

        return $user;
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute([':hash' => $hash, ':id' => $id]);
    }
}
