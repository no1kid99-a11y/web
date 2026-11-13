<?php
/**
 * Bảng route. Middleware: 'auth' (đăng nhập), 'guest', 'role:<vai trò>', 'can:<quyền>'.
 * @var \Core\Router $router
 */
use App\Controllers\AccountController;
use App\Controllers\Admin;
use App\Controllers\Api\SlotController;
use App\Controllers\AuthController;
use App\Controllers\BookingController;
use App\Controllers\HomeController;
use App\Controllers\PetController;
use App\Controllers\PhotoController;
use App\Controllers\Staff;

// --- Công khai ---
$router->get('/', [HomeController::class, 'index']);
$router->get('/services', [HomeController::class, 'services']);

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

// --- Tài khoản (mọi vai trò) ---
$router->get('/password', [AccountController::class, 'showPassword'], ['auth']);
$router->post('/password', [AccountController::class, 'updatePassword'], ['auth']);
$router->get('/profile', [AccountController::class, 'showProfile'], ['auth']);
$router->post('/profile', [AccountController::class, 'updateProfile'], ['auth']);

// --- Ảnh ca làm: một route cho mọi vai trò, quyền xem kiểm tra theo đơn (BookingPolicy::view) ---
$router->get('/photos/{id}', [PhotoController::class, 'show'], ['auth']);

// --- Khách hàng ---
$router->group('', ['auth', 'role:client'], function ($r) {
    $r->get('/pets', [PetController::class, 'index']);
    $r->get('/pets/create', [PetController::class, 'create']);
    $r->post('/pets', [PetController::class, 'store']);
    $r->get('/pets/{id}', [PetController::class, 'show']);
    $r->get('/pets/{id}/edit', [PetController::class, 'edit']);
    $r->post('/pets/{id}', [PetController::class, 'update']);
    $r->post('/pets/{id}/delete', [PetController::class, 'destroy']);
    $r->post('/pets/{id}/vaccinations', [PetController::class, 'addVaccination']);
    $r->post('/pets/{id}/vaccinations/{vid}/delete', [PetController::class, 'deleteVaccination']);

    $r->get('/book', [BookingController::class, 'wizard']);
    $r->post('/bookings', [BookingController::class, 'store']);
    $r->get('/bookings', [BookingController::class, 'index']);
    $r->get('/bookings/{id}', [BookingController::class, 'show']);
    $r->post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    $r->post('/bookings/{id}/review', [BookingController::class, 'review']);
    $r->get('/bookings/{id}/camera', [BookingController::class, 'camera']);
    $r->get('/bookings/{id}/photos', [BookingController::class, 'photoFeed']);
    $r->get('/camera', [BookingController::class, 'cameraEntry']);

    $r->get('/api/slots', [SlotController::class, 'forClient']);
});

// --- Nhân viên ---
$router->group('/staff', ['auth', 'role:staff'], function ($r) {
    $r->get('', [Staff\ScheduleController::class, 'index']);
    $r->get('/bookings/{id}', [Staff\ScheduleController::class, 'show']);
    $r->post('/bookings/{id}/start', [Staff\ScheduleController::class, 'start']);
    $r->post('/bookings/{id}/complete', [Staff\ScheduleController::class, 'complete']);
    $r->post('/bookings/{id}/health', [Staff\ScheduleController::class, 'health']);
    $r->post('/bookings/{id}/photos', [Staff\ScheduleController::class, 'uploadPhoto'], ['can:photo.upload']);
    $r->post('/photos/{id}/delete', [Staff\ScheduleController::class, 'deletePhoto'], ['can:photo.upload']);
    $r->get('/reviews', [Staff\ScheduleController::class, 'reviews']);
});

// --- Quản trị ---
$router->group('/admin', ['auth', 'role:admin'], function ($r) {
    $r->get('', [Admin\DashboardController::class, 'index'], ['can:report.view']);

    $r->get('/bookings', [Admin\BookingController::class, 'index'], ['can:booking.view_all']);
    $r->get('/bookings/export', [Admin\BookingController::class, 'export'], ['can:booking.view_all']);
    $r->get('/bookings/{id}', [Admin\BookingController::class, 'show'], ['can:booking.view_all']);
    $r->post('/bookings/{id}/approve', [Admin\BookingController::class, 'approve'], ['can:booking.approve']);
    $r->post('/bookings/{id}/cancel', [Admin\BookingController::class, 'cancel'], ['can:booking.cancel_any']);
    $r->post('/bookings/{id}/start', [Admin\BookingController::class, 'start'], ['can:booking.progress']);
    $r->post('/bookings/{id}/complete', [Admin\BookingController::class, 'complete'], ['can:booking.progress']);
    $r->post('/bookings/{id}/reassign', [Admin\BookingController::class, 'reassign'], ['can:booking.reassign']);
    $r->post('/bookings/{id}/reschedule', [Admin\BookingController::class, 'reschedule'], ['can:booking.reschedule']);
    $r->post('/bookings/{id}/health', [Admin\BookingController::class, 'health'], ['can:health.write']);
    $r->post('/bookings/{id}/photos', [Admin\BookingController::class, 'uploadPhoto'], ['can:photo.upload']);
    $r->post('/photos/{id}/delete', [Admin\BookingController::class, 'deletePhoto'], ['can:photo.upload']);
    $r->get('/api/slots', [SlotController::class, 'forAdmin'], ['can:booking.reschedule']);
    $r->get('/schedule', [Admin\ScheduleController::class, 'index'], ['can:booking.view_all']);
    $r->get('/pets/{id}', [Admin\UserController::class, 'pet'], ['can:user.manage']);

    $r->get('/users', [Admin\UserController::class, 'index'], ['can:user.manage']);
    $r->get('/users/create', [Admin\UserController::class, 'create'], ['can:user.manage']);
    $r->post('/users', [Admin\UserController::class, 'store'], ['can:user.manage']);
    $r->get('/users/{id}', [Admin\UserController::class, 'show'], ['can:user.manage']);
    $r->post('/users/{id}', [Admin\UserController::class, 'update'], ['can:user.manage']);
    $r->post('/users/{id}/lock', [Admin\UserController::class, 'lock'], ['can:user.manage']);
    $r->post('/users/{id}/unlock', [Admin\UserController::class, 'unlock'], ['can:user.manage']);
    $r->post('/users/{id}/role', [Admin\UserController::class, 'role'], ['can:user.manage']);
    $r->post('/users/{id}/reset-password', [Admin\UserController::class, 'resetPassword'], ['can:user.manage']);


    $r->get('/services', [Admin\CatalogController::class, 'services'], ['can:catalog.manage']);
    $r->get('/services/create', [Admin\CatalogController::class, 'createService'], ['can:catalog.manage']);
    $r->post('/services', [Admin\CatalogController::class, 'storeService'], ['can:catalog.manage']);
    $r->get('/services/{id}/edit', [Admin\CatalogController::class, 'editService'], ['can:catalog.manage']);
    $r->post('/services/{id}', [Admin\CatalogController::class, 'updateService'], ['can:catalog.manage']);
    $r->post('/services/{id}/toggle', [Admin\CatalogController::class, 'toggleService'], ['can:catalog.manage']);

    $r->get('/combos', [Admin\CatalogController::class, 'combos'], ['can:catalog.manage']);
    $r->get('/combos/create', [Admin\CatalogController::class, 'createCombo'], ['can:catalog.manage']);
    $r->post('/combos', [Admin\CatalogController::class, 'storeCombo'], ['can:catalog.manage']);
    $r->get('/combos/{id}/edit', [Admin\CatalogController::class, 'editCombo'], ['can:catalog.manage']);
    $r->post('/combos/{id}', [Admin\CatalogController::class, 'updateCombo'], ['can:catalog.manage']);
    $r->post('/combos/{id}/toggle', [Admin\CatalogController::class, 'toggleCombo'], ['can:catalog.manage']);

    $r->get('/slots', [Admin\SlotController::class, 'index'], ['can:slot.manage']);
    $r->post('/slots', [Admin\SlotController::class, 'store'], ['can:slot.manage']);
    $r->post('/slots/{id}', [Admin\SlotController::class, 'update'], ['can:slot.manage']);
    $r->post('/slots/{id}/toggle', [Admin\SlotController::class, 'toggle'], ['can:slot.manage']);

    $r->get('/reviews', [Admin\ReviewController::class, 'index'], ['can:review.moderate']);
    $r->post('/reviews/{id}/toggle', [Admin\ReviewController::class, 'toggle'], ['can:review.moderate']);

    $r->get('/logs', [Admin\LogController::class, 'index'], ['can:log.view']);
});
