<?php

declare(strict_types=1);

// Автозагрузка классов приложения для тестов
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

date_default_timezone_set('Asia/Dushanbe');
