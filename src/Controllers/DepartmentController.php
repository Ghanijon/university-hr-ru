<?php

declare(strict_types=1);

namespace App\Controllers;

final class DepartmentController extends Controller
{
    /** GET /departments */
    public function index(): void
    {
        // День 12: заглушка. На день 13 — DepartmentRepository и PositionRepository.
        $departments = [
            ['id' => 1, 'name' => 'Кафедра программирования и информационных систем',
             'faculty_name' => 'Факультет информационных технологий и коммуникаций',
             'head_name' => 'Назаров Джамшед Хакимович', 'employees_count' => 7],
            ['id' => 2, 'name' => 'Кафедра сетей связи и систем коммутации',
             'faculty_name' => 'Факультет информационных технологий и коммуникаций',
             'head_name' => 'Шарипов Хусейн Ахмадович', 'employees_count' => 5],
        ];
        $positions = [
            ['title' => 'Доцент', 'category' => 'academic'],
            ['title' => 'Лаборант', 'category' => 'support'],
        ];

        $this->render('departments/index', [
            'title'       => 'Кафедры',
            'active'      => 'departments',
            'departments' => $departments,
            'positions'   => $positions,
        ]);
    }
}
