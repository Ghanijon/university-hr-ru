<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Модульные тесты класса App\Core\Validator.
 * Каждый тест — маленький сценарий «дано → действие → проверка» (Arrange — Act — Assert).
 */
final class ValidatorTest extends TestCase
{
    public function testRequiredFailsForEmptyAndWhitespace(): void
    {
        $v = new Validator(['a' => '', 'b' => '   ']);
        $v->required('a', 'Поле А')->required('b', 'Поле Б')->required('c', 'Поле В');

        $this->assertTrue($v->fails());
        $this->assertSame([
            'a' => 'Поле «Поле А» обязательно',
            'b' => 'Поле «Поле Б» обязательно',
            'c' => 'Поле «Поле В» обязательно',
        ], $v->errors());
    }

    public function testRequiredPassesForFilledValue(): void
    {
        $v = (new Validator(['last_name' => 'Рахимов']))->required('last_name', 'Фамилия');

        $this->assertFalse($v->fails());
        $this->assertSame([], $v->errors());
    }

    public function testMaxLengthCountsCyrillicLettersAsOneCharacter(): void
    {
        // 60 букв «х» — это 120 байт в UTF-8 (по 2 байта D1 85), но 60 символов: ошибки быть не должно
        $ok = (new Validator(['name' => str_repeat('х', 60)]))->maxLength('name', 60, 'Имя');
        $tooLong = (new Validator(['name' => str_repeat('х', 61)]))->maxLength('name', 60, 'Имя');

        $this->assertFalse($ok->fails());
        $this->assertTrue($tooLong->fails());
    }

    /**
     * @dataProvider emailProvider
     */
    #[DataProvider('emailProvider')]
    public function testEmail(string $email, bool $valid): void
    {
        $v = (new Validator(['email' => $email]))->email('email', 'E-mail');

        $this->assertSame(!$valid, $v->fails());
    }

    public static function emailProvider(): array
    {
        return [
            'обычный адрес'      => ['f.rahimov@example.tj', true],
            'поддомен'           => ['hr@mail.polytech.tj', true],
            'без @'              => ['f.rahimov.example.tj', false],
            'без домена'         => ['f.rahimov@', false],
            'пробел внутри'      => ['f rahimov@example.tj', false],
            'пустое (необязательное поле)' => ['', true],
        ];
    }

    /**
     * @dataProvider dateProvider
     */
    #[DataProvider('dateProvider')]
    public function testDate(string $date, bool $valid): void
    {
        $v = (new Validator(['d' => $date]))->date('d', 'Дата');

        $this->assertSame(!$valid, $v->fails());
    }

    public static function dateProvider(): array
    {
        return [
            'корректная дата'          => ['2026-11-02', true],
            '29 февраля високосного'   => ['2028-02-29', true],
            '29 февраля невисокосного' => ['2026-02-29', false],
            '31 апреля'                => ['2026-04-31', false],
            'формат ДД.ММ.ГГГГ'        => ['02.11.2026', false],
            'месяц 13'                 => ['2026-13-01', false],
            'лишний текст'             => ['2026-11-02abc', false],
            'пустое'                   => ['', true],
        ];
    }

    public function testInAcceptsOnlyAllowedValues(): void
    {
        $ok = (new Validator(['gender' => 'f', 'department_id' => '3']))
            ->in('gender', ['m', 'f'], 'Пол')
            ->in('department_id', [1, 2, 3], 'Кафедра');      // числа из БД сравниваются как строки
        $bad = (new Validator(['gender' => 'x']))->in('gender', ['m', 'f'], 'Пол');

        $this->assertFalse($ok->fails());
        $this->assertSame(['gender' => 'Поле «Пол»: недопустимое значение'], $bad->errors());
    }

    public function testPatternForPhone(): void
    {
        $regex = '/^[0-9+()\s\-]{5,20}$/';
        $ok = (new Validator(['phone' => '+992 92 701-11-02']))->pattern('phone', $regex, 'Ошибка');
        $bad = (new Validator(['phone' => '<script>']))->pattern('phone', $regex, 'Ошибка');

        $this->assertFalse($ok->fails());
        $this->assertTrue($bad->fails());
    }

    public function testOnlyFirstErrorPerFieldIsKept(): void
    {
        $v = (new Validator(['email' => '']))
            ->required('email', 'E-mail')
            ->addError('email', 'Вторая ошибка');

        $this->assertSame(['email' => 'Поле «E-mail» обязательно'], $v->errors());
    }

    public function testArrayValueIsTreatedAsEmpty(): void
    {
        // Злоумышленник может прислать last_name[]=x — это не строка
        $v = (new Validator(['last_name' => ['x']]))->required('last_name', 'Фамилия');

        $this->assertTrue($v->fails());
    }
}
