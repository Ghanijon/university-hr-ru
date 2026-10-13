<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRequestRepository;

final class DashboardController extends Controller
{
    /** GET / */
    public function index(): void
    {
        $byStatus = (new EmployeeRepository())->countByStatus();
        $leaves = new LeaveRequestRepository();

        $this->render('dashboard/index', [
            'title'  => 'Главная',
            'active' => 'dashboard',
            'stats'  => [
                'active'      => $byStatus['active'],
                'on_leave'    => $byStatus['on_leave'],
                'pending'     => $leaves->countByStatus('pending'),
                'departments' => count((new DepartmentRepository())->all()),
            ],
            'latestLeaves' => $leaves->latest(5),
        ]);
    }
}
