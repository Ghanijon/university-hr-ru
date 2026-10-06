<?php
// public/api/employees/index.php — ВРЕМЕННАЯ заглушка API для дня 11.
// В лекции 14 маршрут /api/employees появится в front controller, а эту папку нужно удалить.
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$employees = [
    ['id' => 1, 'full_name' => 'Рахимов Фарход Комилович', 'department' => 'Программирование и ИС',
     'position' => 'Старший преподаватель', 'phone' => '+992 92 700 11 22', 'hire_date' => '2015-09-01', 'status' => 'active'],
    ['id' => 2, 'full_name' => 'Кодирова Мадина Саидовна', 'department' => 'Высшая математика',
     'position' => 'Доцент', 'phone' => '+992 93 455 66 01', 'hire_date' => '2011-02-15', 'status' => 'on_leave'],
    ['id' => 3, 'full_name' => 'Хусейнов Умед Бахромович', 'department' => 'Электроэнергетика',
     'position' => 'Ассистент', 'phone' => null, 'hire_date' => '2022-09-01', 'status' => 'active'],
    ['id' => 4, 'full_name' => 'Ганиева Нигина Рустамовна', 'department' => 'Программирование и ИС',
     'position' => 'Преподаватель', 'phone' => '+992 90 123 45 67', 'hire_date' => '2019-03-11', 'status' => 'dismissed'],
    // Запись для проверки экранирования: такое «имя» не должно выполниться как HTML
    ['id' => 5, 'full_name' => '<img src=x onerror="alert(\'XSS\')"> Тестов', 'department' => 'Тестовая кафедра',
     'position' => 'Лаборант', 'phone' => null, 'hire_date' => '2024-01-10', 'status' => 'active'],
];

$q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
if ($q !== '') {
    $employees = array_values(array_filter(
        $employees,
        fn (array $e): bool => str_contains(mb_strtolower($e['full_name']), $q)
    ));
}

echo json_encode($employees, JSON_UNESCAPED_UNICODE);
