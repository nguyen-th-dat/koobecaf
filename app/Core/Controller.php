<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function view(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        echo View::render($template, $data, $layout);
    }

    /**
     * Dừng xử lý vì dữ liệu không hợp lệ: lưu lại giá trị đã nhập, báo lỗi và chuyển hướng.
     *
     * @param array<int,string> $keep Tên các field cần giữ lại khi chuyển hướng.
     */
    protected function fail(string $message, string $redirect, array $keep = []): void
    {
        OldInput::flash($this->oldValues($keep));
        setFlashMessage('error', $message);
        Response::redirect($redirect);
    }

    /**
     * Như fail() nhưng quay lại trang vừa truy cập.
     *
     * @param array<int,string> $keep
     */
    protected function failBack(string $message, string $fallback = '/', array $keep = []): void
    {
        OldInput::flash($this->oldValues($keep));
        setFlashMessage('error', $message);
        Response::back($fallback);
    }

    /**
     * Thu thập giá trị đã nhập của các field được yêu cầu giữ lại.
     *
     * @param array<int,string> $keep
     *
     * @return array<string,string>
     */
    protected function oldValues(array $keep): array
    {
        $values = [];
        foreach ($keep as $field) {
            $values[$field] = $this->request->string($field);
        }

        return $values;
    }
}
