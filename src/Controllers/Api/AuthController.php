<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiAuth;
use App\Core\ApiException;
use App\Core\Auth;
use App\Core\Validator;

final class AuthController extends ApiController
{
    /** POST /api/v1/auth/login */
    public function login(): void
    {
        $input = $this->input('username', 'password');
        $v = (new Validator($input))->required('username', 'Логин')->required('password', 'Пароль');
        if ($v->fails()) {
            throw ApiException::validation($v->errors());
        }

        $user = Auth::verifyCredentials(trim($input['username']), $input['password']);
        if ($user === null) {
            throw new ApiException(401, 'invalid_credentials', 'Неверный логин или пароль');
        }

        $this->json(ApiAuth::issue((int) $user['id']) + ['user' => [
            'id'            => (int) $user['id'],
            'username'      => $user['username'],
            'role'          => $user['role'],
            'full_name'     => $user['full_name'] ?: null,
            'employee_id'   => $user['employee_id'],
            'department_id' => $user['department_id'],
        ]]);
    }

    /** POST /api/v1/auth/logout */
    public function logout(): void
    {
        ApiAuth::revoke();
        $this->noContent();
    }
}
