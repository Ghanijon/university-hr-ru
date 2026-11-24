<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * Помощники для частей SQL, которые нельзя передать одним параметром.
 * Правило: значения — только через плейсхолдеры, имена столбцов — только из белого списка.
 */
final class Sql
{
    /**
     * Плейсхолдеры для IN(): один параметр — одно значение, поэтому для трёх значений нужны три имени.
     *   Sql::in('status', ['active', 'on_leave'])
     *   → [':status_0, :status_1', [':status_0' => 'active', ':status_1' => 'on_leave']]
     * $name пишет программист, от пользователя приходят только $values.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function in(string $name, array $values): array
    {
        if ($values === []) {
            throw new InvalidArgumentException('IN () без значений — ошибка синтаксиса SQL');
        }

        $params = [];
        foreach (array_values($values) as $i => $value) {
            $params[":{$name}_{$i}"] = $value;
        }

        return [implode(', ', array_keys($params)), $params];
    }

    /**
     * ORDER BY по белому списку. «-hire_date» — по убыванию. Пустая строка — порядок по умолчанию.
     * Ввод пользователя в SQL не попадает: он лишь ключ, по которому берём готовое выражение.
     *
     * @param array<string, string> $allowed ключ из адреса → выражение SQL
     */
    public static function orderBy(string $sort, array $allowed, string $default): string
    {
        if ($sort === '') {
            return $default;
        }
        $descending = str_starts_with($sort, '-');
        $key = $descending ? substr($sort, 1) : $sort;
        if (!isset($allowed[$key])) {
            throw new InvalidArgumentException('Сортировка по этому полю недоступна');
        }

        // Порядок по умолчанию в конце — чтобы одинаковые значения не «прыгали» между страницами
        return $allowed[$key] . ($descending ? ' DESC' : ' ASC') . ', ' . $default;
    }

    /**
     * Экранирует спецсимволы LIKE: % (любая строка), _ (любой символ) и сам \.
     * Плейсхолдер защищает от инъекции, но не от этого: поиск «%» без экранирования нашёл бы всех.
     */
    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
