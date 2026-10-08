<?php

declare(strict_types=1);

namespace App\Controllers;

final class DashboardController extends Controller
{
    /** GET / */
    public function index(): void
    {
        // День 12: числа-заглушки. На день 13 они будут считаться SQL-запросами.
        $stats = ['active' => 44, 'on_leave' => 2, 'pending' => 4, 'departments' => 10];

        $this->render('dashboard/index', [
            'title'  => 'Главная',
            'active' => 'dashboard',
            'stats'  => $stats,
        ]);
    }
}
