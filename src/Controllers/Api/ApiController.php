<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiException;
use JsonException;
use Throwable;

/** Общее для всех контроллеров REST API: разбор JSON, параметры списка, ответы и ошибки. */
abstract class ApiController extends Controller
{
    private const MAX_PER_PAGE = 100;

    /** Любая ошибка API → JSON единого формата */
    public static function sendException(Throwable $e, bool $debug): void
    {
        if (!$e instanceof ApiException) {
            error_log((string) $e);
            // Текст внутренней ошибки (в нём может быть SQL) — только в режиме отладки
            $e = new ApiException(500, 'server_error', $debug ? $e->getMessage() : 'Внутренняя ошибка сервера');
        }
        if ($e->status === 401) {
            header('WWW-Authenticate: Bearer');
        }
        $error = ['code' => $e->errorCode, 'message' => $e->getMessage()];
        if ($e->fields !== []) {
            $error['fields'] = $e->fields;
        }

        http_response_code($e->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $error], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    protected function noContent(): never
    {
        http_response_code(204);
        header('Content-Type: application/json; charset=utf-8');
        exit;
    }

    /**
     * Тело запроса (объект JSON), только перечисленные поля.
     * Числа приводим к строкам: Validator общий с веб-формой, а из формы всегда приходят строки.
     *
     * @return array<string, string>
     */
    protected function input(string ...$fields): array
    {
        $raw = (string) file_get_contents('php://input');
        try {
            $body = trim($raw) === '' ? [] : json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ApiException(400, 'bad_json', 'Тело запроса — не корректный JSON');
        }
        if (!is_array($body) || ($body !== [] && array_is_list($body))) {
            throw new ApiException(400, 'bad_json', 'Тело запроса должно быть объектом JSON');
        }

        $input = $errors = [];
        foreach ($fields as $field) {
            $value = $body[$field] ?? '';
            if (is_string($value) || is_int($value)) {
                $input[$field] = (string) $value;
            } else {
                $errors[$field] = 'Ожидается строка или целое число';
            }
        }
        if ($errors) {
            throw ApiException::validation($errors);
        }

        return $input;
    }

    /** Строковый параметр адреса. ?q[]=1 придёт массивом — это ошибка клиента. */
    protected function query(string $name): string
    {
        $value = $_GET[$name] ?? '';
        if (!is_string($value)) {
            throw ApiException::validation([$name => 'Ожидается одно значение']);
        }

        return trim($value);
    }

    /** Значения через запятую: ?status=active,on_leave */
    protected function queryList(string $name): array
    {
        $value = $this->query($name);

        return $value === '' ? [] : array_values(array_unique(array_map('trim', explode(',', $value))));
    }

    /** @return array{0: int, 1: int} [страница, размер страницы] */
    protected function paging(): array
    {
        return [$this->positiveInt('page', 1, 1_000_000), $this->positiveInt('per_page', 10, self::MAX_PER_PAGE)];
    }

    private function positiveInt(string $name, int $default, int $max): int
    {
        $value = $this->query($name);
        if ($value === '') {
            return $default;
        }
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => $max]]);
        if ($number === false) {
            throw ApiException::validation([$name => "Целое число от 1 до {$max}"]);
        }

        return $number;
    }

    /** Ответ-список: {data: [...], meta: {page, per_page, total}} */
    protected function jsonPage(array $data, int $page, int $perPage, int $total): never
    {
        $this->json(['data' => $data, 'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total]]);
    }
}
