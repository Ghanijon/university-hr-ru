<?php

declare(strict_types=1);

namespace App\Controllers;

final class AuthController extends Controller
{
    /** GET /login — пока только форма; проверка пароля появится на день 14 */
    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Вход', 'username' => ''], 200, 'auth/layout');
    }
}
