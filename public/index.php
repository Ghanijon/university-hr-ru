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
use App\Controllers\ReportController;
use App\Core\Database;
use App\Core\Router;
use App\Core\View;

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
// Настройки и подключение к БД
// ---------------------------------------------------------------------
$configFile = BASE_PATH . '/config/config.php';
if (!is_file($configFile)) {
    $configFile = BASE_PATH . '/config/config.example.php'; // значения по умолчанию для XAMPP
}
$config = require $configFile;

ini_set('display_errors', $config['debug'] ? '1' : '0');
error_reporting(E_ALL);
Database::configure($config['db']);

// Любое непойманное исключение → страница 500 (подробности — только в режиме debug)
set_exception_handler(function (Throwable $e) use ($config): void {
    error_log((string) $e);
    http_response_code(500);
    echo (new View(BASE_PATH . '/views'))->render('errors/error', [
        'title'   => 'Внутренняя ошибка сервера',
        'code'    => 500,
        'message' => $config['debug'] ? $e->getMessage() : 'Попробуйте позже или обратитесь к администратору.',
    ]);
});

// ---------------------------------------------------------------------
// Таблица маршрутов
// ---------------------------------------------------------------------
$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/employees', [EmployeeController::class, 'index']);
$router->get('/employees/create', [EmployeeController::class, 'create']);
$router->post('/employees/create', [EmployeeController::class, 'store']);
$router->get('/employees/{id}', [EmployeeController::class, 'show']);
$router->get('/employees/{id}/edit', [EmployeeController::class, 'edit']);
$router->post('/employees/{id}/edit', [EmployeeController::class, 'update']);
$router->post('/employees/{id}/delete', [EmployeeController::class, 'delete']);

$router->get('/departments', [DepartmentController::class, 'index']);

$router->get('/reports/staff', [ReportController::class, 'staff']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
