<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class PositionRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    public function all(): array
    {
        return $this->db->query('SELECT id, title, category FROM positions ORDER BY category, id')->fetchAll();
    }

    public function ids(): array
    {
        return array_map('intval', $this->db->query('SELECT id FROM positions')->fetchAll(PDO::FETCH_COLUMN));
    }
}
