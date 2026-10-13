<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DepartmentRepository;

final class ReportController extends Controller
{
    /** GET /reports/staff — отчёт «штат по кафедрам» */
    public function staff(): void
    {
        $rows = (new DepartmentRepository())->staffReport();

        // Итоговая строка считается в PHP, чтобы не делать второй запрос
        $totals = ['total' => 0, 'academic' => 0, 'with_degree' => 0, 'on_leave' => 0];
        foreach ($rows as $row) {
            foreach ($totals as $key => $sum) {
                $totals[$key] = $sum + (int) $row[$key];
            }
        }

        $this->render('reports/staff', [
            'title'  => 'Штат по кафедрам',
            'active' => 'reports',
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }
}
