<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;

/**
 * Базовый контроллер: общие методы для всех контроллеров.
 */
abstract class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View(dirname(__DIR__, 2) . '/views');
    }

    /** Выводит страницу: шаблон + общий макет (layout) */
    protected function render(string $template, array $data = [], int $status = 200, string $layout = 'layout'): void
    {
        http_response_code($status);
        echo $this->view->render($template, $data, $layout);
    }

    /** Перенаправление (HTTP 302) — используется после успешной отправки формы (шаблон PRG) */
    protected function redirect(string $path): never
    {
        header('Location: ' . $this->view->url($path));
        exit;
    }

    /** Ответ в формате JSON (для API) */
    protected function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Страница ошибки (403, 404, 419) и завершение скрипта */
    protected function abort(int $code, string $message = ''): never
    {
        $titles = [403 => 'Доступ запрещён', 404 => 'Не найдено', 419 => 'Сессия устарела'];
        $this->render('errors/error', [
            'title'   => $titles[$code] ?? 'Ошибка',
            'code'    => $code,
            'message' => $message,
        ], $code);
        exit;
    }

    /** Сообщение, которое покажется на следующей странице после редиректа */
    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /** Требует вход и одну из ролей. Возвращает текущего пользователя. */
    protected function requireRole(string ...$roles): array
    {
        return Auth::requireRole(...$roles);
    }

    /** Проверка CSRF-токена. Вызывайте в начале КАЖДОГО обработчика POST. */
    protected function verifyCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            $this->abort(419, 'Форма устарела или отправлена с чужого сайта. Обновите страницу и повторите действие.');
        }
    }
}
