<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

/**
 * Аутентификация (кто вы?) и авторизация (что вам можно?).
 * В сессии храним только id пользователя; сами данные читаем из БД на каждом запросе,
 * чтобы заблокированный пользователь сразу потерял доступ.
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';

    /** Кэш пользователя на время одного запроса */
    private static ?array $user = null;

    /** Хеш-заглушка: проверяем пароль даже для несуществующего логина, чтобы время ответа было одинаковым */
    private const DUMMY_HASH = '$2y$10$mdBA2yShM42tlrJ7vr1BLuRHIXOaODbOzsknp/JcSMdMS42NzVUie';

    public static function attempt(string $username, string $password): bool
    {
        $users = new UserRepository();
        $user = $users->findByUsername($username);

        $hash = $user['password_hash'] ?? self::DUMMY_HASH;
        $passwordOk = password_verify($password, $hash);

        if ($user === null || !$passwordOk || !self::isUsable($user)) {
            return false;
        }

        // Если алгоритм или «стоимость» хеширования изменились — обновляем хеш
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $user['id']);
        $users->touchLastLogin((int) $user['id']);
        self::$user = null;

        return true;
    }

    /**
     * Текущий пользователь или null.
     * Ключи: id, username, role, employee_id, department_id, full_name.
     */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = Session::get(self::SESSION_KEY);
        if (!is_int($id)) {
            return null;
        }

        $user = (new UserRepository())->find($id);
        if ($user === null || !self::isUsable($user)) {
            Session::remove(self::SESSION_KEY);
            return null;
        }

        return self::$user = $user;
    }

    /**
     * Учётная запись активна и, для ролей head и employee, связана с карточкой сотрудника.
     * Без карточки неизвестны кафедра и «свои» заявки — такой пользователь не должен работать,
     * иначе фильтры по кафедре и сотруднику не применятся и он увидит чужие данные.
     */
    private static function isUsable(array $user): bool
    {
        if (!(int) $user['is_active']) {
            return false;
        }
        if (in_array($user['role'], ['head', 'employee'], true)) {
            return !empty($user['employee_id']) && !empty($user['department_id']);
        }

        return true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();

        return $user !== null && in_array($user['role'], $roles, true);
    }

    /** Гость → на страницу входа. Возвращает текущего пользователя. */
    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            // Запомним, куда шёл пользователь, — вернём его туда после входа
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
                Session::set('intended', $_SERVER['REQUEST_URI'] ?? '/');
            }
            header('Location: ' . Router::basePath() . '/login');
            exit;
        }

        return $user;
    }

    /** Нет нужной роли → 403. Возвращает текущего пользователя. */
    public static function requireRole(string ...$roles): array
    {
        $user = self::requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo (new View(dirname(__DIR__, 2) . '/views'))->render('errors/error', [
                'title'   => 'Доступ запрещён',
                'code'    => 403,
                'message' => 'У вашей роли нет прав для этой страницы.',
            ]);
            exit;
        }

        return $user;
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::destroy();
    }
}
