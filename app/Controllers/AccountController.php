<?php
declare(strict_types=1);

namespace App\Controllers;

use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Session;

/**
 * Hồ sơ và mật khẩu của chính người dùng (mọi vai trò).
 * Nhân viên và Admin không tự đổi email; khách hàng đổi được.
 */
class AccountController extends Controller
{
    private function layoutFor(array $u): string
    {
        return $u['role'] === 'client' ? 'layouts/main' : 'layouts/panel';
    }

    public function showPassword(): void
    {
        $u = $this->user();
        $this->render('account/password', ['title' => 'Đổi mật khẩu', 'forced' => (int)$u['must_change_password'] === 1], $this->layoutFor($u));
    }

    public function updatePassword(): void
    {
        $u = $this->user();
        $data = [
            'current_password' => $_POST['current_password'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
        ];
        $this->validate($data, ['current_password' => 'required', 'password' => 'required|min:8|max:72|confirmed'], '/password');
        if (!password_verify($data['current_password'], $u['password'])) {
            flash('error', 'Mật khẩu hiện tại không đúng.');
            redirect('/password');
        }
        if (password_verify($data['password'], $u['password'])) {
            flash('error', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
            redirect('/password');
        }
        Database::update('users', [
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'must_change_password' => 0,
            'updated_at' => Database::now(),
        ], 'id = :id', ['id' => $u['id']]);
        Session::regenerate();
        flash('success', 'Đã đổi mật khẩu.');
        redirect(Auth::home($u));
    }

    public function showProfile(): void
    {
        $u = $this->user();
        $this->render('account/profile', ['title' => 'Thông tin cá nhân', 'u' => $u], $this->layoutFor($u));
    }

    public function updateProfile(): void
    {
        $u = $this->user();
        $isClient = $u['role'] === 'client';
        $data = [
            'name' => $this->input('name'),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
        ];
        $rules = ['name' => 'required|min:2|max:100', 'phone' => 'required|phone', 'address' => 'nullable|max:255'];
        if ($isClient) {
            $data['email'] = mb_strtolower((string)$this->input('email', ''));
            $rules['email'] = 'required|email|max:150|unique:users,email,' . (int)$u['id'];
        }
        $v = $this->validate($data, $rules, '/profile');
        $update = [
            'phone' => preg_replace('/[\s.]/', '', $v['phone']),
            'address' => $v['address'],
            'updated_at' => Database::now(),
        ];
        // Tên của nhân viên/Admin do Admin quản lý để nhật ký và lịch không lệch; khách hàng tự sửa.
        if ($isClient) {
            $update['name'] = $v['name'];
            $update['email'] = $v['email'];
        }
        Database::update('users', $update, 'id = :id', ['id' => $u['id']]);
        flash('success', 'Đã lưu thông tin.');
        redirect('/profile');
    }
}
