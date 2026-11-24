<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Repositories\DepartmentRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\PositionRepository;

/**
 * Правила для данных сотрудника — одни и те же для веб-формы и REST API.
 */
final class EmployeeValidator
{
    public function __construct(
        private readonly EmployeeRepository $employees,
        private readonly DepartmentRepository $departments,
        private readonly PositionRepository $positions,
    ) {
    }

    /**
     * Проверяет данные формы.
     * @return array{0: array, 1: array<string, string>} [очищенные данные, ошибки]
     */
    public function validate(array $input, ?int $id): array
    {
        $v = new Validator($input);
        $v->required('last_name', 'Фамилия')->maxLength('last_name', 60, 'Фамилия')
          ->required('first_name', 'Имя')->maxLength('first_name', 60, 'Имя')
          ->maxLength('middle_name', 60, 'Отчество')
          ->required('birth_date', 'Дата рождения')->date('birth_date', 'Дата рождения')
          ->required('gender', 'Пол')->in('gender', ['m', 'f'], 'Пол')
          ->maxLength('phone', 20, 'Телефон')
          ->pattern('phone', '/^[0-9+()\s\-]{5,20}$/', 'Телефон: только цифры, пробелы, +, -, скобки')
          ->maxLength('email', 120, 'E-mail')->email('email', 'E-mail')
          ->required('department_id', 'Кафедра')->in('department_id', $this->departments->ids(), 'Кафедра')
          ->required('position_id', 'Должность')->in('position_id', $this->positions->ids(), 'Должность')
          ->required('academic_degree', 'Учёная степень')
          ->in('academic_degree', ['none', 'candidate', 'doctor', 'phd'], 'Учёная степень')
          ->required('hire_date', 'Дата приёма')->date('hire_date', 'Дата приёма')
          ->required('status', 'Статус')->in('status', ['active', 'on_leave', 'dismissed'], 'Статус');

        // Приводим данные к виду для БД: обрезаем пробелы, пустые необязательные поля → NULL
        $data = [];
        foreach (EmployeeRepository::FIELDS as $field) {
            $value = trim(is_scalar($input[$field] ?? '') ? (string) ($input[$field] ?? '') : '');
            $data[$field] = $value === '' ? null : $value;
        }

        // Правила, которые зависят от других данных
        $errors = $v->errors();
        if (!isset($errors['birth_date']) && $data['birth_date'] > date('Y-m-d', strtotime('-18 years'))) {
            $v->addError('birth_date', 'Сотруднику должно быть не меньше 18 лет');
        }
        if (!isset($errors['hire_date']) && $data['hire_date'] > date('Y-m-d')) {
            $v->addError('hire_date', 'Дата приёма не может быть в будущем');
        }
        if (!isset($errors['email']) && $data['email'] !== null && $this->employees->emailExists($data['email'], $id)) {
            $v->addError('email', 'Сотрудник с таким e-mail уже есть');
        }

        return [$data, $v->errors()];
    }
}
