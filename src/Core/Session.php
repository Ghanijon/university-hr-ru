<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Обёртка над $_SESSION. Сессия хранит данные пользователя между запросами;
 * браузер получает только идентификатор в cookie.
 */
final class Session
{
    private const FLASH_KEY = '_flash';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        // XAMPP: приложение открывается и как /university-hr/, и как /university-hr/public/ —
        // cookie должна работать для обоих адресов, поэтому «/public» в конце пути отбрасываем
        $cookiePath = preg_replace('#/public$#', '', Router::basePath()) ?: '/';

        session_name('UHRSESSID');
        session_set_cookie_params([
            'lifetime' => 0,                        // до закрытия браузера
            'path'     => $cookiePath,
            'httponly' => true,                     // cookie недоступна из JavaScript (защита от кражи через XSS)
            'secure'   => $isHttps,                 // по HTTPS — только по защищённому каналу
            'samesite' => 'Lax',                    // не отправлять cookie в POST-запросах с чужих сайтов
        ]);
        ini_set('session.use_strict_mode', '1');    // не принимать придуманные злоумышленником id сессии
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Сообщение «на один показ»: сохраняем перед редиректом, выводим на следующей странице */
    public static function flash(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY] = [$type, $message];
    }

    /** Достаёт flash-сообщение и сразу удаляет его */
    public static function pullFlash(): ?array
    {
        $flash = $_SESSION[self::FLASH_KEY] ?? null;
        unset($_SESSION[self::FLASH_KEY]);

        return $flash;
    }

    /** Новый идентификатор сессии после входа — защита от фиксации сессии (session fixation) */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
    }
}
