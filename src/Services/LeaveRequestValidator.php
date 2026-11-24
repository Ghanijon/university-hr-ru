<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Repositories\EmployeeRepository;
use App\Repositories\LeaveRequestRepository;

/**
 * Проверка новой заявки на отпуск — общая для веб-формы и REST API.
 * Правила дат и длительности — в LeaveRequestRules.
 */
final class LeaveRequestValidator
{
    public function __construct(private readonly LeaveRequestRepository $leaves)
    {
    }

    /** @return array{0: array, 1: array<string, string>} [данные для БД, ошибки] */
    public function validate(array $input, array $user): array
    {
        // Сотрудник и заведующий подают заявку только за себя; ОК и администратор — за любого сотрудника
        $onBehalf = in_array($user['role'], ['admin', 'hr'], true);
        $employeeId = (int) ($onBehalf ? $this->text($input, 'employee_id') : $user['employee_id']);

        $v = new Validator($input);
        $v->required('type', 'Тип отпуска')->in('type', LeaveRequestRules::TYPES, 'Тип отпуска')
          ->required('start_date', 'Дата начала')
          ->required('end_date', 'Дата окончания')
          ->maxLength('reason', 255, 'Причина');
        $errors = $v->errors();

        $employee = $employeeId > 0 ? (new EmployeeRepository())->find($employeeId) : null;
        if ($employee === null) {
            $errors['employee_id'] = 'Выберите сотрудника';
        } elseif ($employee['status'] === 'dismissed') {
            $errors['employee_id'] = 'Уволенный сотрудник не может подать заявку';
        }

        $data = [
            'employee_id' => $employeeId,
            'type'        => $this->text($input, 'type'),
            'start_date'  => $this->text($input, 'start_date'),
            'end_date'    => $this->text($input, 'end_date'),
            'reason'      => $this->text($input, 'reason') ?: null,
        ];

        // Бизнес-правила дат — в отдельном тестируемом классе
        if (!isset($errors['start_date']) && !isset($errors['end_date'])) {
            $errors += LeaveRequestRules::validateDates($data['start_date'], $data['end_date'], $data['type']);
        }
        if (!$errors && $this->leaves->hasOverlap($employeeId, $data['start_date'], $data['end_date'])) {
            $errors['start_date'] = 'На эти даты у сотрудника уже есть заявка';
        }

        return [$data, $errors];
    }

    private function text(array $input, string $field): string
    {
        $value = $input[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
