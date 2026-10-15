<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Защита от CSRF (подделки межсайтовых запросов).
 * В каждую POST-форму добавляем скрытое поле со случайным токеном из сессии.
 * Чужой сайт не знает токен, поэтому его поддельная форма будет отклонена.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));   // 64 случайных шестнадцатеричных символа
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    /** Скрытое поле для формы: <?= \App\Core\Csrf::field() ?> */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }

    public static function verify(mixed $token): bool
    {
        // hash_equals сравнивает строки за постоянное время (защита от атаки по времени)
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }
}
