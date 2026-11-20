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
    /** Khoá lưu bước xác nhận của luồng quên mật khẩu trong session. */
    private const CONFIRM_KEY = '_pwd_reset_confirm';

    /** Khoá lưu mã 6 số đã xác thực, dùng để sang bước đặt mật khẩu mới. */
    private const CODE_KEY = '_pwd_reset_code';

    /** Khoá đếm số lần người dùng nhập sai mã 6 số. */
    private const CODE_ATTEMPTS_KEY = '_pwd_reset_code_attempts';

    /** Số lần thử sai tối đa cho mã 6 số trong cùng một phiên. */
    private const MAX_CODE_ATTEMPTS = 5;

    /**
     * Trang yêu cầu quên mật khẩu.
     */
    public function showForgotForm(): void
    {
        $this->view('auth/forgot-password', ['title' => 'Quên mật khẩu'], 'layouts/auth');
    }

    /**
     * Kiểm tra tài khoản/email có tồn tại hay không.
     *
     * Chưa gửi email ở bước này — người dùng phải bấm "Tiếp tục" thì mới gửi.
     */
    public function checkAccount(): void
    {
        $identifier = $this->request->string('email');

        $validator = (new Validator($this->request->all()))
            ->required('email', 'Email hoặc tên đăng nhập');

        if ($validator->fails()) {
            Response::error((string) $validator->firstError(), 422);
        }

        if (!self::isValidIdentifier($identifier)) {
            Response::error('Email không hợp lệ, hoặc tên đăng nhập không đúng định dạng.', 422);
        }

        $user = User::findByIdentifier($identifier);

        if ($user === null) {
            Response::error('Không tìm thấy tài khoản nào khớp với thông tin bạn đã nhập.', 404);
        }

        Session::set(self::CONFIRM_KEY, [
            'user_id' => (int) $user['id'],
            'email'   => (string) $user['email'],
            'name'    => (string) $user['display_name'],
        ]);

        Response::success([
            'name'  => (string) $user['display_name'],
            'email' => (string) $user['email'],
        ]);
    }

    /**
     * Gửi email chứa link đặt lại mật khẩu và mã 6 số (chạy sau khi người dùng bấm "Tiếp tục").
     */
    public function sendResetLink(): void
    {
        $confirm = Session::get(self::CONFIRM_KEY);
        if (!is_array($confirm) || !isset($confirm['user_id'])) {
            Response::error('Phiên xác nhận đã hết hạn. Vui lòng kiểm tra tài khoản lại.', 419);
        }

        $user = User::find((int) $confirm['user_id']);
        if ($user === null) {
            Session::forget(self::CONFIRM_KEY);
            Response::error('Không tìm thấy tài khoản nào khớp với thông tin bạn đã nhập.', 404);
        }

        $reset = PasswordReset::createWithCode((int) $user['id']);
        $sent = $this->sendMail((array) $user, $reset['token'], $reset['code']);

        Session::forget(self::CONFIRM_KEY);

        if (!$sent) {
            Response::error('Không gửi được email. Vui lòng thử lại sau ít phút.', 500);
        }

        Response::success(['email' => (string) $user['email']]);
    }

    /**
     * Người dùng bấm "Không phải tôi" — huỷ yêu cầu.
     */
    public function cancelReset(): void
    {
        Session::forget(self::CONFIRM_KEY);
        Session::forget(self::CODE_KEY);

        Response::success([]);
    }

    /**
     * Gửi yêu cầu quên mật khẩu từ form (không dùng JavaScript).
     *
     * Gộp cả hai bước: kiểm tra tài khoản rồi gửi email ngay, sau đó chuyển hướng
     * về form kèm thông báo. Bản JavaScript dùng hai endpoint riêng ở trên.
     */
    public function sendResetLinkFallback(): void
    {
        $email = $this->request->string('email');

        $validator = (new Validator($this->request->all()))
            ->required('email', 'Email hoặc tên đăng khẩu');

        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), '/forgot-password', ['email']);
        }

        if (!self::isValidIdentifier($email)) {
            $this->fail('Email không hợp lệ, hoặc tên đăng nhập không đúng định dạng.', '/forgot-password', ['email']);
        }

        $user = User::findByIdentifier($email);

        if ($user !== null) {
            $reset = PasswordReset::createWithCode((int) $user['id']);
            $sent = $this->sendMail((array) $user, $reset['token'], $reset['code']);

            if (!$sent) {
                setFlashMessage('error', 'Không gửi được email. Vui lòng thử lại sau ít phút.');
                Response::redirect('/forgot-password');
            }
        }

        // Luôn trả lời giống nhau để không tiết lộ tài khoản nào đã đăng ký.
        setFlashMessage('success', 'Nếu email này đã được đăng ký, chúng tôi đã gửi link đặt lại mật khẩu. Vui lòng kiểm tra hộp thư.');
        Response::redirect('/forgot-password');
    }

    /**
     * Trang đặt lại mật khẩu.
     *
     * Hai đường vào: bằng token trong link email, hoặc bằng mã 6 số đã xác thực.
     */
    public function showResetForm(): void
    {
        $token = $this->request->string('token');

        if ($token !== '') {
            if (PasswordReset::findValid($token) === null) {
                $this->invalidToken();

                return;
            }

            $this->view('auth/reset-password', ['title' => 'Đặt lại mật khẩu', 'token' => $token], 'layouts/auth');

            return;
        }

        if (Session::has(self::CODE_KEY)) {
            $this->view('auth/reset-password', ['title' => 'Đặt lại mật khẩu', 'token' => ''], 'layouts/auth');

            return;
        }

        $this->invalidToken();
    }

    /**
     * Kiểm tra mã 6 số người dùng nhập từ email.
     *
     * Mã hợp lệ được ghi vào session để sang bước đặt mật khẩu mới.
     */
    public function verifyCode(): void
    {
        $code = $this->request->string('code');

        if (!self::isValidCode($code)) {
            Response::error('Mã phải gồm 6 chữ số.', 422);
        }

        if (self::codeAttempts() >= self::MAX_CODE_ATTEMPTS) {
            Response::error('Bạn đã thử quá nhiều lần. Vui lòng yêu cầu mã mới.', 429);
        }

        $reset = PasswordReset::findValidByCode($code);
        if ($reset === null) {
            self::increaseCodeAttempts();
            Response::error('Mã không đúng hoặc đã hết hạn.', 404);
        }

        Session::set(self::CODE_KEY, [
            'user_id' => (int) $reset['user_id'],
            'code'    => $code,
        ]);
        Session::forget(self::CODE_ATTEMPTS_KEY);

        Response::success(['verified' => true]);
    }

    /**
     * Lưu mật khẩu mới.
     */
    public function reset(): void
    {
        $token = $this->request->string('token');
        $password = (string) $this->request->input('password', '');

        $validator = (new Validator($this->request->all()))
            ->required('password', 'Mật khẩu')
            ->min('password', 8, 'Mật khẩu')
            ->matches('password_confirmation', 'password', 'Mật khẩu xác nhận');

        if ($validator->fails()) {
            $this->fail((string) $validator->firstError(), $this->resetFallbackUrl($token));
        }

        if ($token !== '') {
            $reset = PasswordReset::findValid($token);
            if ($reset === null) {
                $this->invalidToken();

                return;
            }

            User::updatePassword($reset['user_id'], $password);
            PasswordReset::markUsed($token);
        } else {
            $code = Session::get(self::CODE_KEY);
            if (!is_array($code) || !isset($code['user_id'])) {
                $this->invalidToken();

                return;
            }

            $reset = PasswordReset::findValidByCode((string) $code['code']);
            if ($reset === null) {
                Session::forget(self::CODE_KEY);
                $this->invalidToken();

                return;
            }

            User::updatePassword($reset['user_id'], $password);
            PasswordReset::markCodeUsed((string) $code['code']);
        }

        Session::forget(self::CODE_KEY);

        setFlashMessage('success', 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập với mật khẩu mới.');
        Response::redirect('/login');
    }

    /**
     * Gửi email cho người dùng vừa yêu cầu đặt lại mật khẩu.
     */
    private function sendMail(array $user, string $token, string $code): bool
    {
        $resetUrl = absolute_url('/reset-password?token=' . urlencode($token));

        $body = View::render('emails/reset-password', [
            'appName'  => (string) config('app.name', 'Koobecaf'),
            'userName' => (string) $user['display_name'],
            'resetUrl' => $resetUrl,
            'code'     => $code,
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
        Session::forget(self::CODE_KEY);
        setFlashMessage('error', 'Link hoặc mã đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.');
        Response::redirect('/forgot-password');
    }

    /**
     * Đường quay lại form đặt lại mật khẩu khi báo lỗi.
     */
    private function resetFallbackUrl(string $token): string
    {
        return $token !== ''
            ? '/reset-password?token=' . urlencode($token)
            : '/reset-password';
    }

    /**
     * Kiểm tra chuỗi người dùng nhập có phải email hợp lệ hoặc username hợp lệ.
     */
    private static function isValidIdentifier(string $identifier): bool
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return (bool) preg_match('/^[a-zA-Z0-9_]{3,30}$/', $identifier);
    }

    /** Mã đặt lại mật khẩu gồm đúng 6 chữ số. */
    private static function isValidCode(string $code): bool
    {
        return (bool) preg_match('/^\d{6}$/', $code);
    }

    /** Số lần đã nhập sai mã trong phiên hiện tại. */
    private static function codeAttempts(): int
    {
        return (int) Session::get(self::CODE_ATTEMPTS_KEY, 0);
    }

    /** Ghi nhận một lần nhập sai mã. */
    private static function increaseCodeAttempts(): void
    {
        Session::set(self::CODE_ATTEMPTS_KEY, self::codeAttempts() + 1);
    }
}
