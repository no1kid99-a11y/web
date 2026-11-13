<?php
declare(strict_types=1);

namespace Core;

use App\Validators\Validator;

/**
 * Lớp controller cơ sở.
 */
abstract class Controller
{
    protected string $layout = 'layouts/main';

    /** $layout: null = layout mặc định của controller, false = không dùng layout. */
    protected function render(string $view, array $data = [], string|false|null $layout = null): void
    {
        echo View::render($view, $data, $layout === false ? null : ($layout ?? $this->layout));
        Session::forget('_old_input');
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /** Yêu cầu gửi bằng fetch() và muốn nhận JSON (ví dụ form chụp ảnh của nhân viên). */
    protected function wantsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    protected function query(string $key, mixed $default = null): mixed
    {
        $v = $_GET[$key] ?? $default;
        return is_string($v) ? trim($v) : $v;
    }

    protected function user(): array
    {
        $u = Auth::user();
        if (!$u) {
            throw new HttpException(403, 'Bạn cần đăng nhập.');
        }
        return $u;
    }

    /**
     * Kiểm tra dữ liệu; nếu lỗi thì lưu old input, flash lỗi và quay lại form.
     */
    protected function validate(array $data, array $rules, string $back): array
    {
        $v = new Validator($data, $rules);
        if ($v->fails()) {
            $this->withOld($data);
            flash('error', implode('<br>', array_map('e', $v->errors())));
            redirect($back);
        }
        return $v->validated();
    }

    protected function withOld(array $data): void
    {
        unset($data['password'], $data['password_confirmation'], $data['current_password'], $data['_csrf']);
        Session::set('_old_input', $data);
    }

    /** Chạy một thao tác nghiệp vụ; AppException được hiển thị thành flash lỗi. */
    protected function attempt(callable $fn, string $back, ?string $success = null, array $old = []): mixed
    {
        try {
            $result = $fn();
            if ($success) {
                flash('success', $success);
            }
            return $result;
        } catch (AppException $e) {
            if ($old) {
                $this->withOld($old);
            }
            flash($e->kind === 'warning' ? 'warning' : 'error', e($e->getMessage()));
            if (!empty($e->details['bookings'])) {
                Session::set('_affected_bookings', $e->details['bookings']);
            }
            redirect($back);
        }
    }

    protected function notFoundUnless(mixed $cond, string $message = 'Không tìm thấy dữ liệu.'): void
    {
        if (!$cond) {
            throw new HttpException(404, $message);
        }
    }

    protected function forbidUnless(bool $cond, string $message = 'Bạn không có quyền thực hiện thao tác này.'): void
    {
        if (!$cond) {
            throw new HttpException(403, $message);
        }
    }
}
