<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;

final class DictionaryController extends ApiController
{
    /** GET /api/v1/departments */
    public function departments(): void
    {
        ApiAuth::requireRole('admin', 'hr', 'head');

        $this->json(['data' => array_map(fn (array $d) => [
            'id'              => (int) $d['id'],
            'name'            => $d['name'],
            'faculty_id'      => (int) $d['faculty_id'],
            'faculty_name'    => $d['faculty_name'],
            'head_name'       => $d['head_name'] ?: null,
            'employees_count' => (int) $d['employees_count'],
        ], (new DepartmentRepository())->all())]);
    }

    /** GET /api/v1/positions */
    public function positions(): void
    {
        ApiAuth::requireRole('admin', 'hr', 'head');

        $this->json(['data' => (new PositionRepository())->all()]);
    }
}
