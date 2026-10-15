<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use DateTimeImmutable;

/**
 * Бизнес-правила заявки на отпуск.
 * Класс не обращается к БД и к $_POST — только к своим аргументам,
 * поэтому его легко проверить модульными тестами (tests/LeaveRequestRulesTest.php).
 */
final class LeaveRequestRules
{
    /** Ежегодный отпуск педагогических работников — до 56 календарных дней */
    public const MAX_ANNUAL_DAYS = 56;

    public const TYPES = ['annual', 'study', 'unpaid', 'sick'];

    /**
     * Таблица переходов: [текущий статус][действие] => [новый статус, кто может].
     * Статусы approved и rejected — конечные: из них переходов нет.
     */
    private const TRANSITIONS = [
        'pending' => [
            'approve' => ['head_approved', ['head', 'admin']],
            'reject'  => ['rejected', ['head', 'admin']],
        ],
        'head_approved' => [
            'approve' => ['approved', ['hr', 'admin']],
            'reject'  => ['rejected', ['hr', 'admin']],
        ],
    ];

    /**
     * Проверка дат. Возвращает ошибки: ['start_date' => '...', 'end_date' => '...'].
     * $today передаётся параметром, чтобы тест не зависел от текущей даты.
     */
    public static function validateDates(string $start, string $end, string $type, ?DateTimeImmutable $today = null): array
    {
        $errors = [];
        $today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);

        if (!Validator::isValidDate($start)) {
            $errors['start_date'] = 'Укажите корректную дату начала';
        }
        if (!Validator::isValidDate($end)) {
            $errors['end_date'] = 'Укажите корректную дату окончания';
        }
        if ($errors) {
            return $errors;
        }

        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);

        if ($startDate < $today) {
            $errors['start_date'] = 'Дата начала не может быть в прошлом';
        }
        if ($endDate < $startDate) {
            $errors['end_date'] = 'Дата окончания не может быть раньше даты начала';
        } elseif ($type === 'annual' && self::days($start, $end) > self::MAX_ANNUAL_DAYS) {
            $errors['end_date'] = 'Ежегодный отпуск — не более ' . self::MAX_ANNUAL_DAYS . ' календарных дней';
        }

        return $errors;
    }

    /** Длительность в календарных днях, включая первый и последний день */
    public static function days(string $start, string $end): int
    {
        $diff = (new DateTimeImmutable($start))->diff(new DateTimeImmutable($end));

        return $diff->invert ? 0 : $diff->days + 1;
    }

    /**
     * Новый статус после действия или null, если переход запрещён.
     * nextStatus('pending', 'approve', 'head') → 'head_approved'
     */
    public static function nextStatus(string $current, string $action, string $role): ?string
    {
        $rule = self::TRANSITIONS[$current][$action] ?? null;
        if ($rule === null) {
            return null;
        }
        [$next, $roles] = $rule;

        return in_array($role, $roles, true) ? $next : null;
    }

    /**
     * Может ли пользователь выполнить действие с конкретной заявкой.
     * Кроме таблицы переходов проверяем: заведующий решает только по своей кафедре
     * и не может согласовать собственную заявку.
     *
     * @param array{status: string, employee_id: int|string, department_id: int|string} $leave
     * @param array{role: string, employee_id: int|string|null, department_id: int|string|null} $user
     */
    public static function canDecide(array $leave, array $user, string $action): bool
    {
        if (self::nextStatus($leave['status'], $action, $user['role']) === null) {
            return false;
        }
        if ($user['role'] === 'head') {
            $sameDepartment = (int) $leave['department_id'] === (int) ($user['department_id'] ?? 0);
            $ownRequest = (int) $leave['employee_id'] === (int) ($user['employee_id'] ?? 0);

            return $sameDepartment && !$ownRequest;
        }

        return true;
    }
}
