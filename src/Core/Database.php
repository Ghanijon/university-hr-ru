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
            // charset=utf8mb4 — иначе кириллица превратится в «????»
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);

            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // ошибки SQL → исключения
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // строки как ассоциативные массивы
                PDO::ATTR_EMULATE_PREPARES   => false,                  // настоящие prepared statements
            ]);
            // Часовой пояс соединения = часовой пояс PHP, иначе NOW() и CURDATE() в MySQL
            // вернут время сервера БД (часто UTC), а не время Душанбе
            self::$pdo->exec("SET time_zone = '" . date('P') . "'");
        }

        return self::$pdo;
    }
}
