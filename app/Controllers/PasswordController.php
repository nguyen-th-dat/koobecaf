<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\PasswordReset;
use App\Models\User;
use App\Services\MailService;

final class PasswordController extends Controller
{
    /**
     * Trang yêu cầu quên mật khẩu.
     */
    public function showForgotForm(): void
    {
        $this->view('auth/forgot-password', ['title' => 'Quên mật khẩu'], 'layouts/auth');
    }

    /**
     * Gửi email chứa link đặt lại mật khẩu.
     */
    public function sendResetLink(): void
    {
        $email = $this->request->string('email');

        $validator = (new Validator($this->request->all()))
            ->required('email', 'Email')
            ->email('email');

        if ($validator->fails()) {
            $this->fail($validator->firstError(), '/forgot-password', ['email' => $email]);
        }

        $user = User::findByEmail($email);

        if ($user !== null) {
            $token = PasswordReset::create((int) $user['id']);
            $sent = $this->sendMail((array) $user, $token);

            if (!$sent) {
                Session::flash('error', 'Không gửi được email. Vui lòng thử lại sau ít phút.');
                Response::redirect('/forgot-password');
            }
        }

        // Luôn trả lời giống nhau để không tiết lộ email nào đã đăng ký.
        Session::flash('success', 'Nếu email này đã được đăng ký, chúng tôi đã gửi link đặt lại mật khẩu. Vui lòng kiểm tra hộp thư.');
        Response::redirect('/forgot-password');
    }

    /**
     * Trang đặt lại mật khẩu.
     */
    public function showResetForm(): void
    {
        $token = $this->request->string('token');

        if ($token === '' || PasswordReset::findValid($token) === null) {
            $this->invalidToken();

            return;
        }

        $this->view('auth/reset-password', ['title' => 'Đặt lại mật khẩu', 'token' => $token], 'layouts/auth');
    }

    /**
     * Lưu mật khẩu mới.
     */
    public function reset(): void
    {
        $token = $this->request->string('token');
        $password = (string) $this->request->input('password', '');

        $reset = PasswordReset::findValid($token);
        if ($reset === null) {
            $this->invalidToken();

            return;
        }

        $validator = (new Validator($this->request->all()))
            ->required('password', 'Mật khẩu')
            ->min('password', 8, 'Mật khẩu')
            ->matches('password_confirmation', 'password', 'Mật khẩu xác nhận');

        if ($validator->fails()) {
            $this->fail($validator->firstError(), '/reset-password?token=' . urlencode($token));
        }

        User::updatePassword($reset['user_id'], $password);
        PasswordReset::markUsed($token);

        Session::flash('success', 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập với mật khẩu mới.');
        Response::redirect('/login');
    }

    /**
     * Gửi email cho người dùng vừa yêu cầu đặt lại mật khẩu.
     */
    private function sendMail(array $user, string $token): bool
    {
        $resetUrl = absolute_url('/reset-password?token=' . urlencode($token));

        $body = View::render('emails/reset-password', [
            'appName'  => (string) config('app.name', 'Koobecaf'),
            'userName' => (string) $user['display_name'],
            'resetUrl' => $resetUrl,
            'minutes'  => PasswordReset::EXPIRE_MINUTES,
        ], '');

        return MailService::send(
            (string) $user['email'],
            (string) $user['display_name'],
            'Đặt lại mật khẩu ' . (string) config('app.name', 'Koobecaf'),
            $body
        );
    }

    private function invalidToken(): void
    {
        Session::flash('error', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.');
        Response::redirect('/forgot-password');
    }

    /**
     * @param array<string,string> $old
     */
    private function fail(string $message, string $redirect, array $old = []): void
    {
        Session::set('_old', $old);
        Session::flash('error', $message);
        Response::redirect($redirect);
    }
}
