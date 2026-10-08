<?php

declare(strict_types=1);

namespace App\Controllers;

final class EmployeeController extends Controller
{
    /**
     * День 12: временные данные в массиве (как будто пришли из БД).
     * Ключи совпадают с именами столбцов — на день 13 заменим массив на SQL-запрос.
     */
    private const DEMO = [
        2 => ['id' => 2, 'last_name' => 'Рахимов', 'first_name' => 'Фарход', 'middle_name' => 'Джамшедович',
              'birth_date' => '1990-06-21', 'gender' => 'm', 'phone' => '+992 92 701 11 02', 'email' => 'f.rahimov@example.tj',
              'department_name' => 'Кафедра программирования и информационных систем',
              'faculty_name' => 'Факультет информационных технологий и коммуникаций',
              'position_title' => 'Старший преподаватель', 'academic_degree' => 'none',
              'hire_date' => '2014-09-01', 'status' => 'active', 'photo_path' => null],
        3 => ['id' => 3, 'last_name' => 'Хамидова', 'first_name' => 'Мунира', 'middle_name' => 'Рахматовна',
              'birth_date' => '1986-11-02', 'gender' => 'f', 'phone' => null, 'email' => 'm.hamidova@example.tj',
              'department_name' => 'Кафедра программирования и информационных систем',
              'faculty_name' => 'Факультет информационных технологий и коммуникаций',
              'position_title' => 'Доцент', 'academic_degree' => 'candidate',
              'hire_date' => '2010-09-01', 'status' => 'active', 'photo_path' => null],
        14 => ['id' => 14, 'last_name' => 'Назарова', 'first_name' => 'Мавджуда', 'middle_name' => 'Тохировна',
               'birth_date' => '1996-03-22', 'gender' => 'f', 'phone' => null, 'email' => null,
               'department_name' => 'Кафедра высшей математики и физики',
               'faculty_name' => 'Факультет информационных технологий и коммуникаций',
               'position_title' => 'Преподаватель', 'academic_degree' => 'none',
               'hire_date' => '2020-09-01', 'status' => 'on_leave', 'photo_path' => null],
    ];

    /** GET /employees */
    public function index(): void
    {
        $this->render('employees/index', [
            'title'       => 'Сотрудники',
            'active'      => 'employees',
            'employees'   => array_values(self::DEMO),
            'departments' => [],
            'filters'     => ['q' => $_GET['q'] ?? '', 'department_id' => '', 'status' => ''],
            'pagination'  => ['page' => 1, 'pages' => 1, 'total' => count(self::DEMO)],
            'canEdit'     => false,
        ]);
    }

    /** GET /employees/{id} */
    public function show(int $id): void
    {
        $employee = self::DEMO[$id] ?? null;
        if ($employee === null) {
            $this->abort(404, 'Сотрудник не найден');
        }

        $this->render('employees/show', [
            'title'    => $employee['last_name'] . ' ' . $employee['first_name'],
            'active'   => 'employees',
            'employee' => $employee,
            'canEdit'  => false,
        ]);
    }
}
