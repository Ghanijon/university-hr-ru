<?php

/**
 * Пример настроек. Скопируйте файл:
 *   cp config/config.example.php config/config.php
 * и укажите свои параметры. config.php НЕ попадает в Git (см. .gitignore),
 * потому что в нём пароль от базы данных.
 *
 * Любое значение можно задать и переменной окружения (DB_HOST, DB_PASS, APP_DEBUG ...):
 * так удобно на сервере (см. docs/16-deploy.md).
 */

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'university_hr',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',          // в XAMPP у root пароль пустой
    ],

    // Часовой пояс приложения. Без него PHP и MySQL считают «сегодня» по UTC,
    // и с 00:00 до 05:00 по Душанбе дата получается вчерашней.
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Dushanbe',

    // true — показывать подробности ошибок (только при разработке!)
    'debug' => (getenv('APP_DEBUG') ?: '1') === '1',
];
