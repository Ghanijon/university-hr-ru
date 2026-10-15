<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;

final class AuthController extends Controller
{
    /** GET /login */
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->render('auth/login', ['title' => 'Вход', 'username' => '', 'error' => null], 200, 'auth/layout');
    }

    /** POST /login */
    public function login(): void
    {
        $this->verifyCsrf();

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '' || !Auth::attempt($username, $password)) {
            // Не уточняем, что именно неверно — логин или пароль: так сложнее подбирать логины
            $this->render('auth/login', [
                'title'    => 'Вход',
                'username' => $username,
                'error'    => 'Неверный логин или пароль',
            ], 422, 'auth/layout');
            return;
        }

        // Возвращаем пользователя туда, куда он шёл до входа (только внутренний адрес!)
        $intended = (string) Session::get('intended', '');
        Session::remove('intended');
        $base = $this->view->url('');
        $path = str_starts_with($intended, $base . '/') ? substr($intended, strlen($base)) : '/';
        // Защита от открытого редиректа: «//evil.tj» или «/\evil.tj» браузер воспримет как чужой сайт
        if (!preg_match('#^/(?![/\\\\])#', $path)) {
            $path = '/';
        }

        $this->redirect($path);
    }

    /** POST /logout — выход тоже через POST с CSRF-токеном, чтобы чужой сайт не мог «разлогинить» пользователя */
    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        $this->redirect('/login');
    }
}
