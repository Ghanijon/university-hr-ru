<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Database;
use App\Services\TransferService;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Откат транзакции перевода на настоящей MySQL (нужна база стенда, иначе тест пропускается).
 * Данные не меняются: в этом и состоит проверка.
 */
final class TransferTransactionTest extends TestCase
{
    private const EMPLOYEE_ID = 2;
    private const NO_SUCH_USER = 4_000_000_000;

    private PDO $db;

    protected function setUp(): void
    {
        Database::configure([
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('DB_PORT') ?: 3306),
            'name' => getenv('DB_NAME') ?: 'university_hr_ru',
            'user' => getenv('DB_USER') ?: 'hr_demo',
            'pass' => getenv('DB_PASS') ?: 'hr_demo_pass',
        ]);
        try {
            $this->db = Database::connection();
        } catch (PDOException $e) {
            self::markTestSkipped('Нет базы данных: ' . $e->getMessage());
        }
    }

    public function testErrorInSecondQueryRollsBackFirst(): void
    {
        $before = $this->employee();
        $ordersBefore = $this->ordersCount();
        $department = (int) $before['department_id'] === 3 ? 4 : 3;

        // Автор приказа не существует: UPDATE employees пройдёт, а INSERT в orders нарушит внешний ключ
        try {
            (new TransferService($this->db))->transfer(
                self::EMPLOYEE_ID,
                $department,
                (int) $before['position_id'],
                date('Y-m-d'),
                self::NO_SUCH_USER
            );
            self::fail('Ожидалась ошибка внешнего ключа');
        } catch (PDOException $e) {
            self::assertSame(1452, $e->errorInfo[1]);
            // Упал именно второй запрос, значит первый уже был выполнен
            self::assertStringContainsString('fk_orders_created_by', $e->getMessage());
        }

        self::assertFalse($this->db->inTransaction(), 'Транзакция должна быть закрыта');
        self::assertSame($before, $this->employee(), 'Сотрудник не должен измениться');
        self::assertSame($ordersBefore, $this->ordersCount(), 'Приказ не должен появиться');
    }

    private function employee(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM employees WHERE id = :id');
        $stmt->execute([':id' => self::EMPLOYEE_ID]);

        return $stmt->fetch();
    }

    private function ordersCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }
}
