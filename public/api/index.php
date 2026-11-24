<?php

/**
 * Встроенный сервер «php -S» для адресов /api/... ищет index.php сначала в папке public/api.
 * Передаём запрос основному front controller (Nginx и Apache делают это сами).
 */

declare(strict_types=1);

$_SERVER['SCRIPT_NAME'] = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], 2)), '/') . '/index.php';

require dirname(__DIR__) . '/index.php';
