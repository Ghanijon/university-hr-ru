<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Подключение к MySQL через PDO.
 * Одно соединение на весь запрос (создаётся при первом обращении).
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    /** Вызывается один раз в public/index.php */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $c = self::$config;
            // charset=utf8mb4 — полный UTF-8. Без него кириллица превратится в «????».
            // Кодировку задаём именно в DSN, а не запросом SET NAMES: тогда о ней знает и сам PDO.
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);

            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                // Любая ошибка SQL → PDOException. Её нельзя «не заметить», а в транзакции
                // она сразу ведёт в catch, где мы делаем rollBack().
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Строка результата — массив ['last_name' => 'Рахимов'].
                // По умолчанию (FETCH_BOTH) каждое значение повторялось бы ещё и под номером.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // false = настоящие prepared statements: MySQL получает текст запроса и значения
                // РАЗДЕЛЬНО, поэтому значение не может стать частью SQL. true = PDO сам
                // вклеивает экранированные значения в строку запроса.
                // Следствие: одно имя (:q) нельзя использовать в запросе дважды.
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // Часовой пояс соединения = часовой пояс PHP, иначе NOW() и CURDATE() в MySQL
            // вернут время сервера БД (часто UTC), а не время Душанбе
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }

        return self::$pdo;
    }
}
