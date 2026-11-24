<?php

/**
 * Front controller — единая точка входа.
 * Веб-сервер направляет сюда ВСЕ запросы (кроме существующих файлов: CSS, JS, картинок).
 */

declare(strict_types=1);

use App\Controllers\Api\ApiController;
use App\Controllers\Api\AuthController as ApiAuthController;
use App\Controllers\Api\DictionaryController as ApiDictionaryController;
use App\Controllers\Api\EmployeeController as ApiEmployeeController;
use App\Controllers\Api\LeaveController as ApiLeaveController;
use App\Controllers\Api\ReportController as ApiReportController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DepartmentController;
use App\Controllers\EmployeeController;
use App\Controllers\LeaveController;
use App\Controllers\ReportController;
use App\Core\Database;
use App\Core\Router;
use App\Core\Session;
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
$isApi = Router::isApi($_SERVER['REQUEST_URI']);

date_default_timezone_set($config['timezone'] ?? 'Asia/Dushanbe');
// В API предупреждения PHP на экран не выводим: они испортили бы JSON (останутся в логе)
ini_set('display_errors', $config['debug'] && !$isApi ? '1' : '0');
error_reporting(E_ALL);
Database::configure($config['db']);
if (!$isApi) {
    Session::start();   // API узнаёт пользователя по Bearer-токену, cookie ему не нужна
}

// Любое непойманное исключение → страница 500, а в API — JSON с кодом ошибки (подробности — только в режиме debug)
set_exception_handler(function (Throwable $e) use ($config, $isApi): void {
    if ($isApi) {
        ApiController::sendException($e, $config['debug']);
        return;
    }
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
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);

$router->get('/employees', [EmployeeController::class, 'index']);
$router->get('/employees/create', [EmployeeController::class, 'create']);
$router->post('/employees/create', [EmployeeController::class, 'store']);
$router->get('/employees/{id}', [EmployeeController::class, 'show']);
$router->get('/employees/{id}/edit', [EmployeeController::class, 'edit']);
$router->post('/employees/{id}/edit', [EmployeeController::class, 'update']);
$router->post('/employees/{id}/delete', [EmployeeController::class, 'delete']);

$router->get('/departments', [DepartmentController::class, 'index']);

$router->get('/leaves', [LeaveController::class, 'index']);
$router->get('/leaves/create', [LeaveController::class, 'create']);
$router->post('/leaves/create', [LeaveController::class, 'store']);
$router->post('/leaves/{id}/approve', [LeaveController::class, 'approve']);
$router->post('/leaves/{id}/reject', [LeaveController::class, 'reject']);

$router->get('/reports/staff', [ReportController::class, 'staff']);

$router->get('/api/employees', [EmployeeController::class, 'apiSearch']);

// REST API v1 (описание: public/api/openapi.yaml, Swagger UI: /api/docs.html)
$router->post('/api/v1/auth/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/auth/logout', [ApiAuthController::class, 'logout']);

$router->get('/api/v1/employees', [ApiEmployeeController::class, 'index']);
$router->post('/api/v1/employees', [ApiEmployeeController::class, 'store']);
$router->get('/api/v1/employees/{id}', [ApiEmployeeController::class, 'show']);
$router->put('/api/v1/employees/{id}', [ApiEmployeeController::class, 'update']);
$router->delete('/api/v1/employees/{id}', [ApiEmployeeController::class, 'delete']);
$router->post('/api/v1/employees/{id}/transfer', [ApiEmployeeController::class, 'transfer']);

$router->get('/api/v1/departments', [ApiDictionaryController::class, 'departments']);
$router->get('/api/v1/positions', [ApiDictionaryController::class, 'positions']);

$router->get('/api/v1/leaves', [ApiLeaveController::class, 'index']);
$router->post('/api/v1/leaves', [ApiLeaveController::class, 'store']);
$router->post('/api/v1/leaves/{id}/approve', [ApiLeaveController::class, 'approve']);
$router->post('/api/v1/leaves/{id}/reject', [ApiLeaveController::class, 'reject']);

$router->get('/api/v1/reports/staff', [ApiReportController::class, 'staff']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
