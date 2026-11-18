<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\OldInput;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login', ['title' => 'Đăng nhập'], 'layouts/auth');
    }

    public function showRegister(): void
    {
        $this->view('auth/register', ['title' => 'Đăng ký'], 'layouts/auth');
    }

    public function login(): void
    {
        $email = $this->request->string('email');
        $password = (string) $this->request->input('password', '');

        $validator = (new Validator($this->request->all()))
            ->required('email', 'Email')
            ->email('email')
            ->required('password', 'Mật khẩu');

        if ($validator->fails()) {
            $this->failAuth($validator->firstError(), 'login');
        }

        $user = User::findByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->failAuth('Email hoặc mật khẩu không đúng.', 'login');
        }

        $this->loginUser((int) $user['id']);
    }

    public function register(): void
    {
        $username = $this->request->string('username');
        $email = $this->request->string('email');
        $displayName = $this->request->string('display_name');
        $password = (string) $this->request->input('password', '');
        $passwordConfirm = (string) $this->request->input('password_confirmation', '');

        $validator = (new Validator($this->request->all()))
            ->required('username', 'Username')
            ->username('username')
            ->required('email', 'Email')
            ->email('email')
            ->required('display_name', 'Tên hiển thị')
            ->max('display_name', 100, 'Tên hiển thị')
            ->required('password', 'Mật khẩu')
            ->min('password', 8, 'Mật khẩu')
            ->matches('password_confirmation', 'password', 'Mật khẩu xác nhận');

        if ($validator->fails()) {
            $this->failAuth($validator->firstError(), 'register');
        }

        if (User::emailExists($email)) {
            $this->failAuth('Email này đã được sử dụng.', 'register');
        }
        if (User::usernameExists($username)) {
            $this->failAuth('Username này đã tồn tại.', 'register');
        }

        $userId = User::create([
            'username'     => $username,
            'email'        => $email,
            'password'     => $password,
            'display_name' => $displayName,
        ]);

        $this->loginUser($userId, 'Đăng ký tài khoản thành công!');
    }

    public function logout(): void
    {
        Session::destroy();
        Session::start();
        setFlashMessage('success', 'Bạn đã đăng xuất thành công.');
        Response::redirect('/login');
    }

    private function loginUser(int $userId, string $message = 'Đăng nhập thành công!'): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        setFlashMessage('success', $message);
        OldInput::clearDraft('login', 'register');
        Response::redirect('/');
    }

    private function failAuth(string $message, string $redirect): void
    {
        parent::fail($message, '/' . $redirect, ['username', 'email', 'display_name']);
    }
}