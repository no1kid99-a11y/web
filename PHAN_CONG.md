# Phân công công việc – PetCare Home

> File này chỉnh sửa được trực tiếp trên GitHub (bấm biểu tượng bút chì).

Chia theo **module trọn gói**: mỗi người làm một mảng từ database, xử lý cho tới giao diện, nên tự demo và tự giải thích được phần của mình khi bảo vệ.
Hai vấn đề chính của đề cương có người phụ trách riêng: **trùng lịch → Phần 3**, **phân quyền và quản lý người dùng → Phần 1**.

| Phần | Người phụ trách | Nội dung | Nhánh Git |
|---|---|---|---|
| 1 | Long – Trưởng nhóm | Nền tảng, tài khoản và phân quyền | `main` (lần nộp đầu), sau đó `feature/phan-1-nen-tang` |
| 2 | Cương | Khách hàng: thú cưng và đặt lịch | `feature/phan-2-khach-hang` |
| 3 | Hoàng | Lõi đặt lịch và chống trùng lịch | `feature/phan-3-dat-lich` |
| 4 | Tuấn Anh | Nhân viên, danh mục và thống kê | `feature/phan-4-nhan-vien` |

---

## Phần 1 – Nền tảng, tài khoản và phân quyền (Long, trưởng nhóm)

**Chức năng**
- Lõi MVC: Router, Database, Controller, View, Session, CSRF.
- Đăng ký, đăng nhập, đăng xuất, hồ sơ cá nhân, đổi mật khẩu, bắt đổi mật khẩu tạm.
- Phân quyền RBAC: middleware theo nhóm route, bảng quyền `config/permissions.php`.
- Quản lý người dùng: tạo tài khoản nhân viên/Admin, khóa/mở khóa, đổi vai trò, đặt lại mật khẩu.
- Layout và các thành phần giao diện dùng chung (gồm `public/assets/js/photos.js`: lightbox, form chụp ảnh, tự cập nhật).

**Bảng:** `users`; viết và duy trì `schema.sql` cho cả 13 bảng.

**File**
- `core/`, `config/`, `public/` (trừ ảnh upload), `app/Middleware/`, `app/Controllers/Admin/AdminController.php`
- File gốc của repo: `README.md`, `PHAN_CONG.md`, `.gitignore`, `.htaccess`, `index.php`, `composer.json`, các file `.htaccess` chặn truy cập trong từng thư mục
- `database/schema.sql`, `database/install.php`
- `app/Controllers/AuthController.php`, `AccountController.php`, `Admin/UserController.php`
- `app/Services/UserService.php`, `app/Models/User.php`, `app/Models/Model.php`
- `app/Views/auth/`, `account/`, `admin/users/`, `layouts/` (giao diện khách hàng và quản trị), `errors/`, `partials/{head,flash,logo,page_header}.php`
- Ảnh và hình dự phòng trong `public/assets/img/`

**Kiểm thử:** phân quyền (truy cập chéo vai trò, chéo chủ sở hữu), quản lý người dùng.
**Bảo vệ:** mục "Phân quyền và quản lý người dùng" của đề cương.
**Việc chung:** quản lý repo, review và merge Pull Request vào `develop` / `main`.

## Phần 2 – Khách hàng: thú cưng và đặt lịch (Cương)

**Chức năng**
- Trang chủ, trang dịch vụ và bảng giá.
- Hồ sơ thú cưng: thêm, sửa, xóa mềm, upload ảnh, tiêm phòng.
- Booking Wizard 4 bước và API khung giờ trống.
- Danh sách và chi tiết lịch hẹn, hủy đơn, đánh giá.
- Trang **Theo dõi trực tiếp** của khách (ảnh mới tự hiện mỗi 5 giây), route trả ảnh có kiểm tra quyền, album chăm sóc của thú cưng.
- Kiểm tra dữ liệu đầu vào (Validator).

**Bảng:** `pets`, `vaccinations`, `reviews` (phần khách viết), `booking_photos` (phần khách xem).

**File**
- `app/Controllers/HomeController.php`, `PetController.php`, `BookingController.php`, `PhotoController.php`, `Api/SlotController.php`
- `app/Services/PhotoUploader.php`, `app/Validators/Validator.php`, `app/Models/{Pet,Vaccination}.php`
- `app/Views/home/`, `client/`, `partials/{pet_avatar,booking_row_client,vaccination_table,photo_gallery}.php`

**Kiểm thử:** quy tắc đặt lịch (không trộn combo với dịch vụ lẻ, vượt thời lượng khung, đặt sát giờ), upload file giả mạo ảnh, giao diện điện thoại.
**Bảo vệ:** luồng khách hàng từ đăng ký đến đánh giá.

## Phần 3 – Lõi đặt lịch và chống trùng lịch (Hoàng)

**Chức năng**
- `BookingService`: tính khung trống, chọn nhân viên khả dụng, tạo đơn, chuyển trạng thái, đổi nhân viên, dời lịch, tự hủy đơn hết hạn.
- `BookingPolicy` (quyền theo bản ghi).
- Admin quản lý lịch hẹn: duyệt, từ chối, đổi nhân viên, dời lịch.
- Lịch 7 ngày dạng lưới nhân viên × ngày.

**Bảng:** `bookings`, `booking_services`, `time_slots` (phần logic).

**File**
- `app/Services/BookingService.php`, `RetryException.php`
- `app/Policies/BookingPolicy.php`, `app/Models/{Booking,TimeSlot}.php`
- `app/Controllers/Admin/{BookingController,ScheduleController}.php`
- `app/Views/admin/bookings/`, `admin/schedule.php`, `partials/{status_tracker,booking_summary}.php`
- `tests/run.php`, `database/schema.sqlite.sql` (bộ kiểm thử dùng chung; mỗi phần bổ sung kịch bản của mình)

**Kiểm thử:** 7 kịch bản trùng lịch của đề cương (phần lớn nhất của `tests/run.php`).
**Bảo vệ:** mục "Giải quyết vấn đề trùng lịch" (4 lớp: giao diện, server, database, xử lý đồng thời).

## Phần 4 – Nhân viên, danh mục và thống kê (Tuấn Anh)

**Chức năng**
- Khu vực nhân viên: lịch làm việc, bắt đầu và hoàn thành dịch vụ, **chụp ảnh gửi khách**, ghi sổ sức khỏe, xem đánh giá.
- Admin quản lý dịch vụ, combo, khung giờ.
- Dashboard doanh thu (Chart.js), ẩn/hiện đánh giá, nhật ký hoạt động.
- Dữ liệu mẫu.

**Bảng:** `health_records`, `booking_photos` (phần gửi/gỡ ảnh), `services`, `combos`, `combo_service`, `activity_logs`, `time_slots` (phần quản lý danh mục).

**File**
- `app/Controllers/Staff/ScheduleController.php`
- `app/Controllers/Admin/{CatalogController,SlotController,DashboardController,ReviewController,LogController}.php`
- `app/Services/{ActivityLogger,BookingPhotoService}.php`, `app/Models/{HealthRecord,BookingPhoto,Service,Combo,Review,ActivityLog}.php`, `database/seed.php`
- `app/Views/staff/{schedule,booking,reviews}.php`, `admin/{dashboard.php,services,combos,slots,reviews,logs}`, `partials/{health_timeline,log_detail,photo_uploader}.php`

**Kiểm thử:** luồng nhân viên (phải ghi sổ sức khỏe mới hoàn thành được đơn; chỉ nhân viên phụ trách gửi ảnh khi đang làm), quy tắc combo và khung giờ, số liệu dashboard.
**Việc chung:** vẽ ERD, tổng hợp báo cáo, slide, kịch bản demo.

---

## Điểm nối giữa các phần

- **Phần 2 → Phần 3:** Wizard dùng `BookingService::availableSlots()` và `create()`; Phần 2 không tự viết quy tắc chống trùng.
- **Phần 4 → Phần 3:** nhân viên chuyển trạng thái qua `BookingService::transition()`, ghi sổ qua `addHealthRecord()`; quyền gửi ảnh qua `BookingPolicy::uploadPhoto()`.
- **Phần 4 → Phần 2:** ảnh nhân viên gửi (`BookingPhotoService`) hiện ở trang theo dõi của khách qua `BookingPhoto::forBooking()`.
- **Phần 1 → Phần 3:** khóa nhân viên dùng `Booking::futureHoldingForStaff()`.
- **Tất cả → Phần 1:** `Auth::can()`, `Database`, layout chung; **tất cả → Phần 4:** `ActivityLogger::log()`.

## Thứ tự làm

1. Phần 1 xong lõi MVC và `schema.sql` trong 1–2 ngày đầu, merge sớm.
2. Phần 3 làm `BookingService` ngay sau đó (Phần 2 và 4 phụ thuộc vào nó).
3. Ba phần còn lại làm song song.

## Quy ước Git

- Không commit trực tiếp lên `main` và `develop`; mọi thay đổi vào `develop` qua Pull Request, có ít nhất một người khác review.
- Tên commit: `feat:`, `fix:`, `docs:`, `style:` + mô tả ngắn (ví dụ `feat: kiem tra trung khung gio dat lich`).
- Commit dưới đúng tên và email của mình (`git config user.name` / `user.email`), không commit hộ nhau.
- Mỗi người viết chương báo cáo cho phần của mình; Phần 4 ghép thành báo cáo hoàn chỉnh.
