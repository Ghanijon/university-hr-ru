<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

/**
 * Проверка данных формы на сервере.
 *
 *   $v = new Validator($_POST);
 *   $v->required('last_name', 'Фамилия')->maxLength('last_name', 60, 'Фамилия');
 *   if ($v->fails()) { $errors = $v->errors(); }
 *
 * Для каждого поля сохраняется только первая ошибка.
 * Необязательные пустые поля не проверяются (кроме required).
 */
final class Validator
{
    /** @var array<string, string> поле => сообщение */
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') {
            $this->addError($field, "Поле «{$label}» обязательно");
        }

        return $this;
    }

    public function maxLength(string $field, int $max, string $label): self
    {
        if (mb_strlen($this->value($field)) > $max) {
            $this->addError($field, "Поле «{$label}» должно быть не длиннее {$max} символов");
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->addError($field, "Поле «{$label}»: некорректный e-mail");
        }

        return $this;
    }

    /** Дата строго в формате ГГГГ-ММ-ДД и реально существующая (31.02 — ошибка) */
    public function date(string $field, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && !self::isValidDate($value)) {
            $this->addError($field, "Поле «{$label}»: некорректная дата");
        }

        return $this;
    }

    /** Значение должно входить в список допустимых */
    public function in(string $field, array $allowed, string $label): self
    {
        $value = $this->value($field);
        if ($value !== '' && !in_array($value, array_map('strval', $allowed), true)) {
            $this->addError($field, "Поле «{$label}»: недопустимое значение");
        }

        return $this;
    }

    /** Проверка регулярным выражением */
    public function pattern(string $field, string $regex, string $message): self
    {
        $value = $this->value($field);
        if ($value !== '' && preg_match($regex, $value) !== 1) {
            $this->addError($field, $message);
        }

        return $this;
    }

    /** Для проверок, которые нужны только в одном месте (например, уникальность e-mail) */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;

        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
