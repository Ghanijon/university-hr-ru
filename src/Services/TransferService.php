<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Core\Database;
use App\Repositories\EmployeeRepository;
use App\Repositories\OrderRepository;
use PDO;
use Throwable;

/**
 * Перевод сотрудника: новая кафедра/должность + приказ.
 * Два изменения в двух таблицах — поэтому одна транзакция: либо оба, либо ни одного.
 */
final class TransferService
{
    private PDO $db;
    private EmployeeRepository $employees;
    private OrderRepository $orders;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
        // Репозитории получают ТО ЖЕ соединение: транзакция действует внутри одного соединения
        $this->employees = new EmployeeRepository($this->db);
        $this->orders = new OrderRepository($this->db);
    }

    /** @return array{id: int, number: string, date: string, text: string} созданный приказ */
    public function transfer(int $employeeId, int $departmentId, int $positionId, string $date, int $userId): array
    {
        $this->db->beginTransaction();
        try {
            // FOR UPDATE: пока не закончим, никто другой не изменит эту строку (пессимистическая блокировка)
            $employee = $this->employees->findForUpdate($employeeId);
            $this->check($employee, $departmentId, $positionId, $date);

            // Запрос 1: меняем место работы
            $this->employees->setPlace($employeeId, $departmentId, $positionId);

            // Запрос 2: приказ. Если здесь ошибка — запрос 1 тоже отменится
            $number = $this->orders->nextNumber('ПЕ', substr($date, 0, 4));
            $text = $this->orderText($this->employees->find($employeeId), $date);
            $orderId = $this->orders->create($number, 'transfer', $employeeId, $date, $text, $userId);

            $this->db->commit();        // оба изменения становятся постоянными

            return ['id' => $orderId, 'number' => $number, 'date' => $date, 'text' => $text];
        } catch (Throwable $e) {
            $this->db->rollBack();      // возвращаем базу в состояние до beginTransaction()
            throw $e;
        }
    }

    private function check(?array $employee, int $departmentId, int $positionId, string $date): void
    {
        if ($employee === null) {
            throw ApiException::notFound('Сотрудник не найден');
        }
        if ($employee['status'] === 'dismissed') {
            throw ApiException::conflict('Уволенного сотрудника перевести нельзя');
        }
        $sameDepartment = (int) $employee['department_id'] === $departmentId;
        if ($sameDepartment && (int) $employee['position_id'] === $positionId) {
            throw ApiException::validation(['position_id' => 'Сотрудник уже работает на этой кафедре в этой должности']);
        }
        if ($date < $employee['hire_date']) {
            throw ApiException::validation(['date' => 'Дата перевода не может быть раньше даты приёма']);
        }
        if ($date > date('Y-m-d')) {
            throw ApiException::validation(['date' => 'Дата перевода не может быть в будущем']);
        }
        // Права заведующего зависят от его кафедры: после перевода он решал бы чужие заявки
        if (!$sameDepartment && $this->employees->isHead((int) $employee['id'])) {
            throw ApiException::conflict('Сотрудник заведует кафедрой. Сначала назначьте нового заведующего.');
        }
    }

    private function orderText(array $employee, string $date): string
    {
        $name = $employee['last_name'] . ' ' . mb_substr($employee['first_name'], 0, 1) . '.'
            . ($employee['middle_name'] ? ' ' . mb_substr($employee['middle_name'], 0, 1) . '.' : '');

        return sprintf(
            'Перевести: %s. Новое место работы: %s, должность «%s», с %s.',
            $name,
            $employee['department_name'],
            $employee['position_title'],
            date('d.m.Y', strtotime($date))
        );
    }
}
