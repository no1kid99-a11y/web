<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Session;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Đăng nhập']);
    }

    public function login(): void
    {
        $email = mb_strtolower((string)$this->input('email', ''));
        $password = (string)($_POST['password'] ?? '');
        $user = $email !== '' ? User::findByEmail($email) : null;

        // Chống dò mật khẩu đơn giản: giới hạn 5 lần sai liên tiếp trong 5 phút theo phiên.
        $fails = Session::get('_login_fails', ['n' => 0, 't' => 0]);
        if ($fails['n'] >= 5 && time() - $fails['t'] < 300) {
            $this->withOld(['email' => $email]);
            flash('error', 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 5 phút.');
            redirect('/login');
        }

        if (!$user || !password_verify($password, $user['password'])) {
            Session::set('_login_fails', ['n' => $fails['n'] + 1, 't' => time()]);
            $this->withOld(['email' => $email]);
            flash('error', 'Email hoặc mật khẩu không đúng.');
            redirect('/login');
        }
        if ($user['status'] !== 'active') {
            $this->withOld(['email' => $email]);
            flash('error', 'Tài khoản đã bị khóa. Vui lòng liên hệ cửa hàng qua hotline 1900 6868.');
            redirect('/login');
        }
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            Database::update('users', ['password' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $user['id']]);
        }
        Session::forget('_login_fails');
        Auth::login($user);

        $intended = Session::pull('_intended');
        if ((int)$user['must_change_password'] === 1) {
            redirect('/password');
        }
        // Chỉ quay lại trang dự định nếu đúng khu vực của vai trò
        if ($intended && $this->allowedFor($user['role'], $intended)) {
            redirect($intended);
        }
        redirect(Auth::home($user));
    }

    private function allowedFor(string $role, string $path): bool
    {
        if (str_starts_with($path, '/admin')) {
            return $role === 'admin';
        }
        if (str_starts_with($path, '/staff')) {
            return $role === 'staff';
        }
        if (preg_match('#^/(pets|bookings|book)#', $path)) {
            return $role === 'client';
        }
        return true;
    }

    public function showRegister(): void
    {
        $this->render('auth/register', ['title' => 'Tạo tài khoản']);
    }

    /** Đăng ký công khai luôn tạo tài khoản client; form không nhận trường role. */
    public function register(): void
    {
        $data = [
            'name' => $this->input('name'),
            'email' => mb_strtolower((string)$this->input('email', '')),
            'phone' => $this->input('phone'),
            'address' => $this->input('address'),
            'password' => $_POST['password'] ?? '',
            'password_confirmation' => $_POST['password_confirmation'] ?? '',
        ];
        $v = $this->validate($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'required|phone',
            'address' => 'nullable|max:255',
            'password' => 'required|min:8|max:72|confirmed',
        ], '/register');

        $id = Database::insert('users', [
            'name' => $v['name'],
            'email' => $v['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => preg_replace('/[\s.]/', '', $v['phone']),
            'address' => $v['address'],
            'role' => 'client',
            'status' => 'active',
            'must_change_password' => 0,
            'created_at' => Database::now(),
            'updated_at' => Database::now(),
        ]);
        Auth::login(User::find($id));
        flash('success', 'Chào mừng ' . e($v['name']) . '! Hãy thêm hồ sơ thú cưng đầu tiên để đặt lịch.');
        redirect('/pets/create');
    }

    public function logout(): void
    {
        Auth::logout();
        Session::destroy();
        Session::start();
        flash('success', 'Bạn đã đăng xuất.');
        redirect('/login');
    }
}
