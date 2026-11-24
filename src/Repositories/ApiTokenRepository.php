<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

final class ApiTokenRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function create(int $userId, string $hash, DateTimeImmutable $expiresAt): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :hash, :expires_at)');
        $stmt->execute([':user_id' => $userId, ':hash' => $hash, ':expires_at' => $expiresAt->format('Y-m-d H:i:s')]);
    }

    /** id владельца действующего токена */
    public function findUserId(string $hash): ?int
    {
        $stmt = $this->db->prepare('SELECT user_id FROM api_tokens WHERE token_hash = :hash AND expires_at > NOW()');
        $stmt->execute([':hash' => $hash]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    public function delete(string $hash): void
    {
        $stmt = $this->db->prepare('DELETE FROM api_tokens WHERE token_hash = :hash');
        $stmt->execute([':hash' => $hash]);
    }

    public function deleteExpired(): void
    {
        $this->db->exec('DELETE FROM api_tokens WHERE expires_at <= NOW()');
    }
}
