<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRequestRepository;

final class DashboardController extends Controller
{
    /** GET / — содержимое зависит от роли */
    public function index(): void
    {
        $user = $this->requireRole('admin', 'hr', 'head', 'employee');
        $leaves = new LeaveRequestRepository();

        if ($user['role'] === 'employee') {
            $own = ['employee_id' => (int) $user['employee_id']];
            $stats = [
                ['Ожидают решения', $leaves->countByStatus('pending', $own) + $leaves->countByStatus('head_approved', $own)],
                ['Утверждено', $leaves->countByStatus('approved', $own)],
                ['Отклонено', $leaves->countByStatus('rejected', $own)],
            ];
            $latest = $leaves->latest(5, $own);
        } else {
            // Заведующий видит цифры своей кафедры, ОК и администратор — по всему институту
            $scope = $user['role'] === 'head' ? ['department_id' => (int) $user['department_id']] : [];
            $byStatus = (new EmployeeRepository())->countByStatus($scope['department_id'] ?? null);

            // «Ждут вашего решения»: заведующему — pending, ОК — head_approved, админу — оба статуса
            $waiting = match ($user['role']) {
                'head'  => $leaves->countByStatus('pending', $scope),
                'hr'    => $leaves->countByStatus('head_approved'),
                default => $leaves->countByStatus('pending') + $leaves->countByStatus('head_approved'),
            };

            $stats = [
                ['Работают', $byStatus['active']],
                ['В отпуске', $byStatus['on_leave']],
                ['Заявки ждут вашего решения', $waiting],
                $user['role'] === 'head'
                    ? ['Сотрудников кафедры', $byStatus['active'] + $byStatus['on_leave']]
                    : ['Кафедр', count((new DepartmentRepository())->all())],
            ];
            $latest = $leaves->latest(5, $scope);
        }

        $this->render('dashboard/index', [
            'title'        => 'Главная',
            'active'       => 'dashboard',
            'user'         => $user,
            'stats'        => $stats,
            'latestLeaves' => $latest,
        ]);
    }
}
