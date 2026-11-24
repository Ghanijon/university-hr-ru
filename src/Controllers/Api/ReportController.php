<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Repositories\DepartmentRepository;

final class ReportController extends ApiController
{
    /** GET /api/v1/reports/staff */
    public function staff(): void
    {
        ApiAuth::requireRole('admin', 'hr', 'head');

        $data = [];
        $totals = ['total' => 0, 'academic' => 0, 'with_degree' => 0, 'on_leave' => 0];
        foreach ((new DepartmentRepository())->staffReport() as $row) {
            // SUM() возвращает DECIMAL, PDO отдаёт его строкой — приводим к числу
            $numbers = array_map('intval', array_intersect_key($row, $totals));
            foreach ($numbers as $key => $value) {
                $totals[$key] += $value;
            }
            $data[] = [
                'department_id'   => (int) $row['id'],
                'department_name' => $row['department_name'],
                'faculty_name'    => $row['faculty_name'],
            ] + $numbers;
        }

        $this->json(['data' => $data, 'totals' => $totals]);
    }
}
