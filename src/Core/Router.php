<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Маршрутизатор: сопоставляет метод и путь запроса с методом контроллера.
 *
 * Пример:
 *   $router->get('/employees/{id}', [EmployeeController::class, 'show']);
 *   GET /employees/15  →  (new EmployeeController())->show(15)
 */
final class Router
{
    /** @var array<int, array{method: string, regex: string, handler: array{0: class-string, 1: string}}> */
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'regex'   => $this->toRegex($path),
            'handler' => $handler,
        ];
    }

    /**
     * Превращает шаблон пути в регулярное выражение:
     * '/employees/{id}/edit'  →  '#^/employees/(?P<id>\d+)/edit$#'
     * Параметры в фигурных скобках — только цифры (идентификаторы).
     */
    private function toRegex(string $path): string
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path);

        return '#^' . $pattern . '$#';
    }

    /**
     * Находит маршрут и вызывает контроллер.
     * 404 — путь не найден, 405 — путь есть, но метод другой (например, GET вместо POST).
     */
    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalizePath($uri);
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            // Оставляем только именованные параметры: ['id' => '15'] → [15]
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $params = array_map('intval', array_values($params));

            [$class, $action] = $route['handler'];
            (new $class())->$action(...$params);

            return;
        }

        if ($allowedMethods !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($allowedMethods)));
            echo '405 Method Not Allowed';

            return;
        }

        http_response_code(404);
        echo (new View(dirname(__DIR__, 2) . '/views'))->render('errors/error', [
            'title'   => 'Страница не найдена',
            'code'    => 404,
            'message' => 'Проверьте адрес или вернитесь на главную.',
        ]);
    }

    /** Убирает строку запроса (?q=...), базовый путь и лишний слеш в конце. */
    private function normalizePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $base = self::basePath();

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    /**
     * Базовый путь приложения.
     * php -S localhost:8000 -t public          →  ''
     * XAMPP: http://localhost/university-hr/public/  →  '/university-hr/public'
     */
    public static function basePath(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));

        return rtrim($dir, '/');
    }
}
