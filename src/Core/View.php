<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Шаблонизатор на «чистом» PHP.
 * Шаблон — обычный .php-файл в папке views/. Внутри шаблона $this — это объект View,
 * поэтому доступны помощники: $this->e(), $this->url(), $this->partial().
 */
final class View
{
    /** Подписи для значений ENUM из базы данных */
    private const LABELS = [
        'employee_status' => ['active' => 'Работает', 'on_leave' => 'В отпуске', 'dismissed' => 'Уволен'],
        'leave_status'    => [
            'pending'       => 'Ожидает',
            'head_approved' => 'Согласовано зав. кафедрой',
            'approved'      => 'Утверждено',
            'rejected'      => 'Отклонено',
        ],
        'leave_type'      => ['annual' => 'Ежегодный', 'study' => 'Учебный', 'unpaid' => 'Без сохранения з/п', 'sick' => 'По болезни'],
        'degree'          => ['none' => 'Нет', 'candidate' => 'Кандидат наук', 'doctor' => 'Доктор наук', 'phd' => 'PhD'],
        'gender'          => ['m' => 'Мужской', 'f' => 'Женский'],
        'role'            => ['admin' => 'Администратор', 'hr' => 'Отдел кадров', 'head' => 'Зав. кафедрой', 'employee' => 'Сотрудник'],
        'category'        => ['academic' => 'ППС', 'administrative' => 'Администрация', 'support' => 'Вспомогательный персонал'],
    ];

    public function __construct(private readonly string $viewsPath)
    {
    }

    /**
     * Рендерит шаблон и вставляет его в общий макет (layout).
     * Переменная $content в layout.php — это HTML страницы.
     */
    public function render(string $template, array $data = [], ?string $layout = 'layout'): string
    {
        $content = $this->renderFile($template, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($layout, $data + ['content' => $content]);
    }

    /** Подключает фрагмент из views/partials/ */
    public function partial(string $name, array $data = []): string
    {
        return $this->renderFile('partials/' . $name, $data);
    }

    private function renderFile(string $template, array $data): string
    {
        $file = $this->viewsPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Шаблон не найден: {$template}");
        }

        // extract() превращает ['employees' => [...]] в переменную $employees
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /** Экранирование для вывода в HTML — защита от XSS. Используйте при КАЖДОМ выводе данных. */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Полный адрес с учётом базового пути: url('/employees') → '/university-hr/public/employees' */
    public function url(string $path = '/'): string
    {
        return Router::basePath() . $path;
    }

    /** Дата из формата БД (2026-11-02) в формат интерфейса (02.11.2026) */
    public function date(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', substr($value, 0, 10));

        return $date ? $date->format('d.m.Y') : $value;
    }

    /** Подпись для значения ENUM: label('leave_status', 'pending') → 'Ожидает' */
    public function label(string $group, ?string $value): string
    {
        return self::LABELS[$group][$value] ?? (string) $value;
    }

    /** Все подписи группы — для выпадающих списков */
    public function labels(string $group): array
    {
        return self::LABELS[$group] ?? [];
    }

    /** Инициалы для аватара без фото: «Рахимов Фарход» → «РФ» */
    public function initials(string $lastName, string $firstName): string
    {
        return mb_substr($lastName, 0, 1) . mb_substr($firstName, 0, 1);
    }
}
