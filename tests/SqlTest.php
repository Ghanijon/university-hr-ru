<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Sql;
use App\Repositories\EmployeeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Части SQL, которые собираются в коде: IN(), ORDER BY, LIKE. База данных не нужна. */
final class SqlTest extends TestCase
{
    public function testInMakesOnePlaceholderPerValue(): void
    {
        [$sql, $params] = Sql::in('status', ['active', 'on_leave']);

        self::assertSame(':status_0, :status_1', $sql);
        self::assertSame([':status_0' => 'active', ':status_1' => 'on_leave'], $params);
    }

    public function testInNeverPutsValueIntoSql(): void
    {
        [$sql, $params] = Sql::in('status', ["') OR 1=1 -- "]);

        self::assertSame(':status_0', $sql);
        self::assertSame([':status_0' => "') OR 1=1 -- "], $params);
    }

    public function testInIgnoresArrayKeysFromUser(): void
    {
        [$sql] = Sql::in('status', ['x) OR (1=1' => 'active']);

        self::assertSame(':status_0', $sql);
    }

    public function testInWithoutValuesIsError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sql::in('status', []);
    }

    public function testOrderByUsesWhitelist(): void
    {
        self::assertSame('e.hire_date ASC, e.id', Sql::orderBy('hire_date', EmployeeRepository::SORTS, 'e.id'));
        self::assertSame('e.hire_date DESC, e.id', Sql::orderBy('-hire_date', EmployeeRepository::SORTS, 'e.id'));
        self::assertSame('e.id', Sql::orderBy('', EmployeeRepository::SORTS, 'e.id'));
    }

    #[DataProvider('badSorts')]
    public function testOrderByRejectsEverythingElse(string $sort): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sql::orderBy($sort, EmployeeRepository::SORTS, 'e.id');
    }

    public static function badSorts(): array
    {
        return [
            'инъекция'           => ['id; DROP TABLE employees'],
            'столбец не в списке' => ['photo_path'],
            'подзапрос'          => ['(SELECT password_hash FROM users LIMIT 1)'],
            'направление в поле' => ['id DESC'],
            'только минус'       => ['-'],
            'два минуса'         => ['--id'],
        ];
    }

    #[DataProvider('likeValues')]
    public function testEscapeLike(string $input, string $expected): void
    {
        self::assertSame($expected, Sql::escapeLike($input));
    }

    public static function likeValues(): array
    {
        return [
            'обычный текст'  => ['Рахимов', 'Рахимов'],
            'процент'        => ['100%', '100\\%'],
            'подчёркивание'  => ['a_b', 'a\\_b'],
            'обратный слеш'  => ['a\\b', 'a\\\\b'],
            'кавычку не трогаем — её обезвредит параметр' => ["' OR '1'='1", "' OR '1'='1"],
        ];
    }
}
