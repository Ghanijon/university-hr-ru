<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Ошибка REST API: HTTP-статус + код для программы + сообщение для человека.
 * Бросаем из любого места, в JSON её превращает ApiController::sendException().
 */
final class ApiException extends RuntimeException
{
    /** @param array<string, string> $fields ошибки по полям: ['email' => '...'] */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $fields = [],
    ) {
        parent::__construct($message);
    }

    /** @param array<string, string> $fields */
    public static function validation(array $fields): self
    {
        return new self(422, 'validation_failed', 'Проверьте данные запроса', $fields);
    }

    public static function forbidden(string $message = 'У вашей роли нет прав для этого действия'): self
    {
        return new self(403, 'forbidden', $message);
    }

    public static function notFound(string $message): self
    {
        return new self(404, 'not_found', $message);
    }

    public static function conflict(string $message): self
    {
        return new self(409, 'conflict', $message);
    }
}
