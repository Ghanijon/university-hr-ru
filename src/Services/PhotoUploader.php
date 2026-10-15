<?php

declare(strict_types=1);

namespace App\Services;

use finfo;
use RuntimeException;

/**
 * Безопасная загрузка фото сотрудника.
 * Правила: только JPEG/PNG/WebP (проверяем СОДЕРЖИМОЕ файла, а не расширение),
 * не больше 2 МБ, имя файла — случайное (имя от пользователя не используем).
 */
final class PhotoUploader
{
    public const MAX_SIZE = 2 * 1024 * 1024; // 2 МБ

    /** MIME-тип → расширение сохраняемого файла */
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** @param string $publicDir путь к папке public/ */
    public function __construct(private readonly string $publicDir)
    {
    }

    /** Был ли выбран файл в поле формы */
    public static function isProvided(?array $file): bool
    {
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Сохраняет файл в public/uploads и возвращает относительный путь 'uploads/<имя>'.
     * @throws RuntimeException с понятным пользователю сообщением
     */
    public function save(array $file): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('Файл больше 2 МБ');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Не удалось загрузить файл (код ошибки ' . (int) $error . ')');
        }
        // Файл действительно пришёл через HTTP-загрузку, а не подставлен путь к чужому файлу
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Файл не был загружен через форму');
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new RuntimeException('Файл больше 2 МБ');
        }

        // Реальный тип по содержимому («магические байты»), а не по имени и не по $file['type']
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime])) {
            throw new RuntimeException('Допустимы только изображения JPEG, PNG или WebP');
        }
        if (@getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Файл повреждён или не является изображением');
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        $dir = $this->publicDir . '/uploads';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Папка для загрузок недоступна');
        }
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Не удалось сохранить файл');
        }

        return 'uploads/' . $name;
    }

    /** Удаляет старое фото. basename() не даёт выйти за пределы папки uploads (../../) */
    public function delete(?string $path): void
    {
        if ($path === null || !str_starts_with($path, 'uploads/')) {
            return;
        }
        $file = $this->publicDir . '/uploads/' . basename($path);
        if (is_file($file)) {
            unlink($file);
        }
    }
}
