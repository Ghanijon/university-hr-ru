<?php

declare(strict_types=1);

namespace Tests;

use App\Services\LeaveRequestRules;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Модульные тесты бизнес-правил заявки на отпуск.
 * «Сегодня» фиксировано (2026-11-02), чтобы тесты не зависели от даты запуска.
 */
final class LeaveRequestRulesTest extends TestCase
{
    private DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->today = new DateTimeImmutable('2026-11-02');
    }

    // ---------------------------------------------------------------- даты

    public function testValidDatesHaveNoErrors(): void
    {
        $errors = LeaveRequestRules::validateDates('2026-12-21', '2027-01-10', 'annual', $this->today);

        $this->assertSame([], $errors);
    }

    public function testStartTodayIsAllowed(): void
    {
        $this->assertSame([], LeaveRequestRules::validateDates('2026-11-02', '2026-11-02', 'unpaid', $this->today));
    }

    public function testEndBeforeStartIsRejected(): void
    {
        $errors = LeaveRequestRules::validateDates('2026-12-10', '2026-12-01', 'annual', $this->today);

        $this->assertArrayHasKey('end_date', $errors);
        $this->assertSame('Дата окончания не может быть раньше даты начала', $errors['end_date']);
    }

    public function testStartInPastIsRejected(): void
    {
        $errors = LeaveRequestRules::validateDates('2026-11-01', '2026-11-05', 'study', $this->today);

        $this->assertSame(['start_date' => 'Дата начала не может быть в прошлом'], $errors);
    }

    public function testAnnualLeaveOf56DaysIsAllowed(): void
    {
        // 01.12.2026 – 25.01.2027 = 56 календарных дней
        $this->assertSame(56, LeaveRequestRules::days('2026-12-01', '2027-01-25'));
        $this->assertSame([], LeaveRequestRules::validateDates('2026-12-01', '2027-01-25', 'annual', $this->today));
    }

    public function testAnnualLeaveOf57DaysIsRejected(): void
    {
        $errors = LeaveRequestRules::validateDates('2026-12-01', '2027-01-26', 'annual', $this->today);

        $this->assertArrayHasKey('end_date', $errors);
        $this->assertStringContainsString('56', $errors['end_date']);
    }

    public function testLimitOf56DaysAppliesOnlyToAnnualLeave(): void
    {
        $this->assertSame([], LeaveRequestRules::validateDates('2026-12-01', '2027-02-28', 'study', $this->today));
    }

    public function testInvalidDateStringsAreRejected(): void
    {
        $errors = LeaveRequestRules::validateDates('2026-02-30', 'завтра', 'annual', $this->today);

        $this->assertArrayHasKey('start_date', $errors);
        $this->assertArrayHasKey('end_date', $errors);
    }

    /**
     * @dataProvider daysProvider
     */
    #[DataProvider('daysProvider')]
    public function testDaysIncludeFirstAndLastDay(string $start, string $end, int $expected): void
    {
        $this->assertSame($expected, LeaveRequestRules::days($start, $end));
    }

    public static function daysProvider(): array
    {
        return [
            'один день'                => ['2026-11-10', '2026-11-10', 1],
            'неделя'                   => ['2026-11-10', '2026-11-16', 7],
            'через Новый год'          => ['2026-12-21', '2027-01-10', 21],
            'февраль високосного года' => ['2028-02-01', '2028-02-29', 29],
            'конец раньше начала'      => ['2026-11-10', '2026-11-01', 0],
        ];
    }

    // ---------------------------------------------------------------- статусы

    /**
     * @dataProvider transitionProvider
     */
    #[DataProvider('transitionProvider')]
    public function testNextStatus(string $current, string $action, string $role, ?string $expected): void
    {
        $this->assertSame($expected, LeaveRequestRules::nextStatus($current, $action, $role));
    }

    public static function transitionProvider(): array
    {
        return [
            'зав. согласует'               => ['pending', 'approve', 'head', 'head_approved'],
            'зав. отклоняет'               => ['pending', 'reject', 'head', 'rejected'],
            'админ согласует за зав.'      => ['pending', 'approve', 'admin', 'head_approved'],
            'ОК не может перепрыгнуть'     => ['pending', 'approve', 'hr', null],
            'сотрудник не решает'          => ['pending', 'approve', 'employee', null],
            'ОК утверждает'                => ['head_approved', 'approve', 'hr', 'approved'],
            'ОК отклоняет'                 => ['head_approved', 'reject', 'hr', 'rejected'],
            'админ утверждает'             => ['head_approved', 'approve', 'admin', 'approved'],
            'зав. не утверждает дважды'    => ['head_approved', 'approve', 'head', null],
            'утверждённую не изменить'     => ['approved', 'reject', 'admin', null],
            'отклонённую не изменить'      => ['rejected', 'approve', 'hr', null],
            'неизвестное действие'         => ['pending', 'delete', 'admin', null],
        ];
    }

    public function testHeadDecidesOnlyForOwnDepartment(): void
    {
        $head = ['role' => 'head', 'employee_id' => 1, 'department_id' => 1];
        $ownDept = ['status' => 'pending', 'employee_id' => 2, 'department_id' => 1];
        $otherDept = ['status' => 'pending', 'employee_id' => 7, 'department_id' => 2];

        $this->assertTrue(LeaveRequestRules::canDecide($ownDept, $head, 'approve'));
        $this->assertFalse(LeaveRequestRules::canDecide($otherDept, $head, 'approve'));
        $this->assertFalse(LeaveRequestRules::canDecide($otherDept, $head, 'reject'));
    }

    public function testHeadCannotApproveOwnRequest(): void
    {
        $head = ['role' => 'head', 'employee_id' => 1, 'department_id' => 1];
        $own = ['status' => 'pending', 'employee_id' => 1, 'department_id' => 1];
        $admin = ['role' => 'admin', 'employee_id' => null, 'department_id' => null];

        $this->assertFalse(LeaveRequestRules::canDecide($own, $head, 'approve'));
        $this->assertTrue(LeaveRequestRules::canDecide($own, $admin, 'approve'));
    }

    public function testHrSeesAllDepartmentsButOnlySecondLevel(): void
    {
        $hr = ['role' => 'hr', 'employee_id' => null, 'department_id' => null];

        $this->assertTrue(LeaveRequestRules::canDecide(['status' => 'head_approved', 'employee_id' => 7, 'department_id' => 2], $hr, 'approve'));
        $this->assertFalse(LeaveRequestRules::canDecide(['status' => 'pending', 'employee_id' => 7, 'department_id' => 2], $hr, 'approve'));
    }

    // ---------------------------------------------------------------- причина отказа (403 или 409 в API)

    #[DataProvider('denials')]
    public function testDenialReason(?string $expected, string $status, string $role, int $department = 1, int $employee = 2): void
    {
        $leave = ['status' => $status, 'employee_id' => $employee, 'department_id' => $department];
        $user = ['role' => $role, 'employee_id' => 1, 'department_id' => 1];

        self::assertSame($expected, LeaveRequestRules::denialReason($leave, $user, 'approve'));
    }

    public static function denials(): array
    {
        return [
            'зав. кафедрой согласует'        => [null, 'pending', 'head'],
            'зав. кафедрой повторно'         => ['conflict', 'head_approved', 'head'],
            'чужая кафедра'                  => ['forbidden', 'pending', 'head', 2],
            'своя заявка'                    => ['forbidden', 'pending', 'head', 1, 1],
            'чужая кафедра, уже утверждена'  => ['forbidden', 'approved', 'head', 2],
            'ОК раньше зав. кафедрой'        => ['forbidden', 'pending', 'hr'],
            'ОК утверждает'                  => [null, 'head_approved', 'hr'],
            'ОК повторно'                    => ['conflict', 'approved', 'hr'],
            'сотрудник'                      => ['forbidden', 'approved', 'employee'],
        ];
    }
}
