<?php

/**
 * Front controller — единая точка входа.
 * Веб-сервер направляет сюда ВСЕ запросы (кроме существующих файлов: CSS, JS, картинок).
 */

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DepartmentController;
use App\Controllers\EmployeeController;
use App\Core\Router;

define('BASE_PATH', dirname(__DIR__));

// Автозагрузка классов: App\Core\Router → src/Core/Router.php (стандарт PSR-4)
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    // Запасной вариант, если не выполнен composer install
    spl_autoload_register(function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = BASE_PATH . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

// ---------------------------------------------------------------------
// Таблица маршрутов
// ---------------------------------------------------------------------
$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/employees', [EmployeeController::class, 'index']);
$router->get('/employees/{id}', [EmployeeController::class, 'show']);

$router->get('/departments', [DepartmentController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
