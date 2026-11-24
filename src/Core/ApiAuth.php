<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\ApiTokenRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;

/**
 * Вход в REST API по Bearer-токену (заголовок «Authorization: Bearer <токен>»).
 * CSRF-токен здесь не нужен: браузер не добавляет этот заголовок сам, в отличие от cookie.
 */
final class ApiAuth
{
    private const LIFETIME = '+8 hours';

    /** Выдаёт новый токен. Открытый токен видит только клиент, в БД — лишь его хеш. */
    public static function issue(int $userId): array
    {
        $token = bin2hex(random_bytes(32));
        $expires = new DateTimeImmutable(self::LIFETIME);

        $tokens = new ApiTokenRepository();
        $tokens->deleteExpired();
        $tokens->create($userId, self::hash($token), $expires);

        return ['token' => $token, 'expires_at' => $expires->format(DATE_ATOM)];
    }

    /** Нет токена, он неизвестен или просрочен → 401. Возвращает пользователя. */
    public static function requireUser(): array
    {
        $userId = (new ApiTokenRepository())->findUserId(self::hash(self::bearerToken()));
        // Пользователя читаем при каждом запросе: заблокированный сразу теряет доступ
        $user = $userId === null ? null : (new UserRepository())->find($userId);
        if ($user === null || !Auth::isUsable($user)) {
            throw new ApiException(401, 'unauthorized', 'Токен недействителен или истёк. Войдите заново.');
        }

        return $user;
    }

    public static function requireRole(string ...$roles): array
    {
        $user = self::requireUser();
        if (!in_array($user['role'], $roles, true)) {
            throw ApiException::forbidden();
        }

        return $user;
    }

    /** Выход: удаляем только тот токен, с которым пришёл запрос */
    public static function revoke(): void
    {
        self::requireUser();
        (new ApiTokenRepository())->delete(self::hash(self::bearerToken()));
    }

    private static function bearerToken(): string
    {
        // REDIRECT_... — под этим именем заголовок приходит в Apache после mod_rewrite
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([0-9a-f]{64})$/i', trim($header), $m) !== 1) {
            throw new ApiException(401, 'unauthorized', 'Нужен заголовок Authorization: Bearer <токен>');
        }

        return strtolower($m[1]);
    }

    /** Токен случайный и длинный, перебрать его нельзя — хватает быстрого SHA-256 (для паролей нужен bcrypt) */
    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
