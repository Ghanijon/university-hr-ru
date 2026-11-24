<?php

/**
 * Загружает database/dump.sql в ПУСТУЮ базу (например, на Railway):
 *   php database/import.php
 * Если в базе уже есть хотя бы одна таблица, ничего не меняет, поэтому
 * команду безопасно запускать при каждом деплое.
 * Параметры берёт там же, где сайт: config.php или переменные DB_*.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit; // только из командной строки
}

$root = dirname(__DIR__);
$local = $root . '/config/config.php';
$c = (require is_file($local) ? $local : $root . '/config/config.example.php')['db'];

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']),
        $c['user'],
        $c['pass'],
        // Здесь эмуляция нужна: в дампе много команд, а настоящий prepare принимает только одну.
        // Данных от пользователя в этом SQL нет.
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true]
    );

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if ($tables !== []) {
        echo 'В базе уже есть таблицы (' . count($tables) . "), импорт пропущен\n";
        exit(0);
    }

    $sql = file_get_contents($root . '/database/dump.sql');
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('Не удалось прочитать database/dump.sql');
    }

    // nextRowset() проходит по всем командам: иначе ошибка во второй и далее осталась бы незамеченной
    $result = $pdo->query($sql);
    while ($result->nextRowset()) {
    }
    $result->closeCursor();

    echo 'Импорт выполнен, таблиц: ' . count($pdo->query('SHOW TABLES')->fetchAll()) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Ошибка импорта: ' . $e->getMessage() . "\n");
    exit(1); // Railway остановит деплой
}
