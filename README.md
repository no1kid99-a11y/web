# PetCare Home – Website chăm sóc thú cưng tại nhà

## 1. Hướng dẫn cài đặt và chạy web

Hướng dẫn viết cho **Windows + XAMPP**; Laragon và macOS có ghi chú riêng ở từng bước.

### 1.1. Chuẩn bị

| Cần có | Ghi chú |
|---|---|
| **XAMPP bản PHP 8.2 trở lên** | Tải tại apachefriends.org, cài vào `C:\xampp`. Hoặc dùng **Laragon** (cài vào `C:\laragon`). |
| **Kết nối Internet** | Giao diện tải Tailwind, Alpine.js, Chart.js và font từ CDN. Không có mạng thì web mất màu sắc và các nút bấm động. |
| Mã nguồn dự án | File `petcare.zip` hoặc `git clone`. |

Yêu cầu kỹ thuật: PHP 8.2+ có các extension `pdo_mysql`, `mbstring`, `fileinfo`; MySQL 5.7+ hoặc MariaDB 10.2.5+ (cần cột sinh tự động). XAMPP và Laragon bản mới đều đáp ứng.

### 1.2. Giải nén đúng chỗ

Giải nén vào `C:\xampp\htdocs\` (Laragon: `C:\laragon\www\`).

Lỗi hay gặp nhất là bị lồng hai thư mục: lệnh "Extract All" của Windows dễ tạo ra `htdocs\petcare\petcare\...`. Kết quả đúng phải như sau:

```
C:\xampp\htdocs\petcare\
├── app\
├── config\
├── core\
├── database\
├── public\        ← trong đây có index.php
├── tests\
└── README.md
```

Nếu bị lồng, hãy chuyển thư mục `petcare` bên trong ra ngoài.

> Nếu lấy code từ repo GitHub `web` (clone vào `htdocs`), thư mục sẽ tên là `web`, và mọi địa chỉ `localhost/petcare/...` trong hướng dẫn này đổi thành `localhost/web/...`, ví dụ `http://localhost/web/public/`.

### 1.3. Bật Apache và MySQL

1. Mở **XAMPP Control Panel**.
2. Bấm **Start** ở dòng **Apache** và dòng **MySQL**. Cả hai phải chuyển sang màu xanh.

Laragon: bấm **Start All**.

### 1.4. Tạo database

1. Mở trình duyệt, vào `http://localhost/phpmyadmin`.
2. Bấm tab **SQL**, dán câu lệnh sau rồi bấm **Go**:
   ```sql
   CREATE DATABASE petcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Cột bên trái sẽ xuất hiện database `petcare` (đang trống).

Laragon: vào menu **Database** để mở HeidiSQL hoặc phpMyAdmin, rồi chạy cùng câu lệnh.

### 1.5. Cấu hình kết nối (thường bỏ qua được)

Mặc định web kết nối với `root`, mật khẩu rỗng, database `petcare`. Đây cũng là mặc định của XAMPP và Laragon.

Nếu MySQL trên máy bạn có mật khẩu, hoặc bạn đặt tên database khác:
1. Vào thư mục `config`, sao chép `config.local.example.php` thành `config.local.php`.
2. Sửa các dòng `username`, `password`, `database`.

File này không được đưa lên Git, nên mỗi thành viên giữ cấu hình riêng của máy mình.

### 1.6. Tạo bảng và nạp dữ liệu mẫu

1. Trong XAMPP Control Panel, bấm nút **Shell** ở cột bên phải. Cửa sổ dòng lệnh mở ra đã có sẵn lệnh `php`.
   - Laragon: bấm nút **Terminal**.
2. Gõ lần lượt:
   ```bash
   cd C:\xampp\htdocs\petcare
   php -v
   php database/install.php
   ```
   - `php -v` phải ra phiên bản 8.2 trở lên.
   - Lệnh cài chạy thành công sẽ in ra:
     ```
     Driver: mysql
     Database 'petcare' đã sẵn sàng.
     Đã tạo 17 câu lệnh schema.
     Đã nạp dữ liệu mẫu.
     ```
3. Nếu tiếng Việt hiện thành ký tự lạ thì gõ `chcp 65001` rồi chạy lại. Ký tự lạ không ảnh hưởng đến kết quả.

> **Cảnh báo:** lệnh `install.php` **xóa sạch và tạo lại** toàn bộ bảng. Chỉ chạy lại khi muốn làm mới dữ liệu.

### 1.7. Mở web và đăng nhập

Vào **`http://localhost/petcare/public/`**. Gõ `http://localhost/petcare/` cũng được, web sẽ tự chuyển vào `public`.

| Vai trò | Email | Mật khẩu | Sau khi đăng nhập |
|---|---|---|---|
| Quản trị viên | admin@petcare.vn (hoặc trang.tran@petcare.vn) | Admin@123 | Trang Tổng quan |
| Nhân viên | an.le@petcare.vn, binh.pham@petcare.vn, chau.hoang@petcare.vn | Staff@123 | Lịch làm việc |
| Khách hàng | ha.do@gmail.com, huy.vu@gmail.com, lananh.bui@gmail.com | Khach@123 | Lịch hẹn của tôi |

**Chụp ảnh bằng điện thoại thật:**
1. Điện thoại và laptop bắt chung một mạng Wi-Fi.
2. Bật Apache và MySQL trong XAMPP. Xem IP của laptop bằng lệnh `ipconfig` (dòng **IPv4 Address** của Wi-Fi, ví dụ `192.168.1.15`). Nếu Windows hỏi tường lửa cho Apache, chọn **Allow**.
3. Trên điện thoại mở `http://<IP laptop>/petcare/public`, đăng nhập `an.le@petcare.vn`, mở đơn PC-009, bấm **Chụp ảnh**: điện thoại mở thẳng camera sau.
4. Trên laptop đăng nhập khách `ha.do@gmail.com`, mở **Ảnh trực tiếp** để thấy ảnh hiện lên.

Nút *chụp bằng webcam* chỉ hiện khi mở bằng `localhost` (trình duyệt chỉ cho dùng webcam trên localhost hoặc HTTPS); trên điện thoại đã có nút **Chụp ảnh** mở camera nên không cần.

**Cách khác, không cần Apache** (MySQL vẫn phải bật):
```bash
cd C:\xampp\htdocs\petcare
php -S localhost:8000 -t public public/index.php
```
Sau đó mở `http://localhost:8000`. Giữ cửa sổ lệnh này mở trong lúc dùng web.

### 1.8. Kiểm tra nhanh trong 5 phút

Làm theo luồng sau để chắc mọi phần đều chạy. Nên dùng một cửa sổ thường và một cửa sổ ẩn danh để đăng nhập hai tài khoản cùng lúc.

1. **Khách** (ha.do@gmail.com):
   - Bấm **Đặt lịch**, chọn thú cưng, chọn combo, chọn ngày mai và một khung giờ, rồi xác nhận. Đơn mới ở trạng thái *Chờ duyệt*.
   - Bấm **Ảnh trực tiếp** trên menu (hoặc mở đơn *Đang tiến hành*, bấm **Theo dõi trực tiếp**). Để nguyên trang này.
2. **Admin**:
   - Ở **Tổng quan**, bấm **Duyệt** đơn vừa đặt.
   - Mở đơn đó để thử **Đổi nhân viên** và **Dời lịch**.
   - Xem **Lịch tuần nhân viên** và **Nhật ký hoạt động**.
3. **Nhân viên** (an.le@petcare.vn):
   - Mở đơn *Đang tiến hành* (PC-009, bé Lu), ở bước 1 **Chụp ảnh gửi khách**: chọn mốc, bấm **Chụp ảnh** (máy tính thì chọn một ảnh bất kỳ hoặc bấm *chụp bằng webcam*), gõ một dòng ghi chú, bấm **Gửi ảnh cho khách**.
   - Quay sang cửa sổ của khách: sau khoảng 5 giây ảnh tự hiện lên đầu dòng thời gian, không cần tải lại trang.
   - Ghi sổ sức khỏe rồi bấm **Hoàn thành đơn**. Trang của khách tự báo "đã xong buổi chăm sóc".
   - Đăng nhập lại bằng khách Hà để chấm sao cho đơn đó.
4. **Kiểm tra phân quyền:** khi đang đăng nhập nhân viên, gõ thẳng `.../public/admin` vào thanh địa chỉ. Kết quả phải là trang **403**.

> Dữ liệu mẫu tính ngày theo **lúc chạy lệnh cài**: có đơn "hôm nay", đơn "ngày mai", doanh thu 6 tháng trước. Nếu demo vào ngày khác, hãy **chạy lại `php database/install.php` ngay trước buổi demo**. Nếu không, các đơn mẫu sẽ bị lệch ngày và đơn chờ duyệt đã quá giờ sẽ tự hủy với lý do "Hết hạn".

### 1.9. Chạy kiểm thử tự động

```bash
php tests/run.php
```
Kết quả đúng là `34 đạt, 0 lỗi`. Bộ kiểm thử dùng database SQLite tạm nên không đụng đến dữ liệu MySQL (chi tiết ở mục 5). Nếu báo `could not find driver`, xem dòng tương ứng ở mục 1.10.

### 1.10. Lỗi thường gặp

| Dấu hiệu | Nguyên nhân và cách sửa |
|---|---|
| Apache không Start được | Cổng 80 bị Skype, IIS hoặc VMware chiếm. Tắt các ứng dụng đó, hoặc ở dòng Apache bấm **Config → httpd.conf**, sửa `Listen 80` thành `Listen 8080`, rồi dùng `http://localhost:8080/petcare/public/`. |
| MySQL không Start được | Cổng 3306 đang bị một MySQL khác chiếm (thường là MySQL cài riêng). Vào Services của Windows và tắt dịch vụ MySQL đó. |
| `Unknown database 'petcare'` | Chưa làm bước 1.4, hoặc tên database khác với `config.local.php`. |
| `Access denied for user 'root'` | MySQL có mật khẩu. Làm bước 1.5. |
| `'php' is not recognized...` | Bạn đang dùng cmd thường. Hãy mở cửa sổ lệnh bằng nút **Shell** của XAMPP, hoặc gõ đường dẫn đầy đủ `C:\xampp\php\php.exe database/install.php`. |
| Trang chủ hiện được nhưng bấm link nào cũng **404 của Apache** | Sai cấu trúc thư mục (xem bước 1.2) hoặc Apache chưa bật mod_rewrite. Trong `httpd.conf`, kiểm tra dòng `LoadModule rewrite_module` không có dấu `#` ở đầu, và thư mục htdocs có `AllowOverride All`. XAMPP bật sẵn cả hai. |
| Web không có màu, nút bấm không phản hồi, biểu đồ trống | Máy không có Internet nên không tải được CDN. |
| Trang **419 "Phiên làm việc đã hết hạn"** | Tải lại trang rồi thử lại. Đừng mở lẫn `localhost` và `127.0.0.1` trong cùng phiên làm việc. |
| `Class "finfo" not found` khi tải ảnh, hoặc `Call to undefined function mb_strlen` | Mở **Config → PHP (php.ini)**, bỏ dấu `;` trước `extension=fileinfo` và `extension=mbstring`, rồi Stop và Start lại Apache. |
| Chạy kiểm thử báo `could not find driver` | Trong php.ini, bỏ dấu `;` trước `extension=pdo_sqlite`. Chỉ cần cho kiểm thử, web vẫn chạy bình thường khi thiếu. |
| Không đặt được lịch, giờ hiển thị bị lệch | Đồng hồ hoặc múi giờ của máy sai. Web tính theo giờ Việt Nam, và chỉ cho đặt trước giờ hẹn ít nhất 2 giờ. |

### 1.11. macOS và làm việc nhóm

- **macOS (XAMPP):** thư mục web là `/Applications/XAMPP/xamppfiles/htdocs/`. Lệnh PHP là `/Applications/XAMPP/xamppfiles/bin/php database/install.php`.
- **Làm việc nhóm:** mỗi thành viên làm lại đủ các bước 1.2–1.6 trên máy mình. Ai sửa cấu trúc bảng thì sửa trong `database/schema.sql`, và cả nhóm chạy lại lệnh cài sau khi kéo code mới về.

## 2. Chức năng theo vai trò

**Khách hàng:** đăng ký, đăng nhập, sửa hồ sơ, đổi mật khẩu · hồ sơ thú cưng (ảnh, tiêm phòng, sổ sức khỏe, xóa mềm) · Booking Wizard 4 bước (một combo hoặc dịch vụ lẻ, chỉ hiện khung còn trống) · lịch hẹn, lọc trạng thái, hủy trước 2 giờ · **theo dõi trực tiếp**: ảnh nhân viên chụp theo từng mốc tự hiện sau vài giây, tự báo khi xong; ảnh lưu trong lịch hẹn và *Album chăm sóc* của thú cưng · đánh giá 1–5 sao.

**Nhân viên:** lịch làm việc của tôi (hôm nay, sắp tới, 14 ngày qua) · bắt đầu dịch vụ trong ngày hẹn · **chụp ảnh gửi khách** ở từng mốc (điện thoại mở thẳng camera, ảnh được thu nhỏ trước khi gửi, gỡ được ảnh chụp nhầm khi ca còn đang làm) · ghi sổ sức khỏe (cập nhật cân nặng hồ sơ) · hoàn thành đơn (bắt buộc đã ghi sổ) · xem đánh giá đơn của mình. Không duyệt, hủy, dời đơn hay xem dữ liệu ngoài đơn được giao.

**Quản trị viên:** tổng quan (doanh thu 6 tháng, đơn chờ duyệt, nhân viên trong tháng) · quản lý lịch hẹn (lọc, duyệt, từ chối/hủy kèm lý do, đổi nhân viên, dời lịch, xử lý sự cố) · lịch 7 ngày dạng lưới nhân viên × ngày · người dùng (tạo nhân viên/Admin với mật khẩu tạm, sửa, khóa/mở khóa, đổi vai trò, đặt lại mật khẩu) · dịch vụ, combo, khung giờ · ẩn/hiện đánh giá · gỡ ảnh không phù hợp, gửi ảnh bổ sung · nhật ký hoạt động.

## 3. Giải quyết trùng lịch – đọc code ở đâu

| Lớp | Cách làm | File |
|---|---|---|
| 1. Giao diện | Wizard gọi `/api/slots`, chỉ hiện khung còn nhân viên khả dụng | `app/Views/client/bookings/wizard.php`, `app/Controllers/Api/SlotController.php` |
| 2. Server | Mọi luồng (đặt, duyệt, đổi NV, dời lịch, khóa TK) đi qua cùng bộ kiểm tra | `app/Services/BookingService.php`, `UserService.php` |
| 3. Database | `staff_id NOT NULL`, cột sinh `active_flag`, chỉ mục duy nhất `uq_staff_slot`, `uq_pet_slot` | `database/schema.sql` |
| 4. Đồng thời | Giao dịch PDO, khóa dòng nhân viên (`FOR UPDATE` trên dòng có sẵn), bắt lỗi 1062/1213 và thử lại, đổi trạng thái có điều kiện `WHERE status = ?` | `BookingService::create()`, `applyTransition()`, `core/Database.php` |

"Nhân viên khả dụng" = role staff, đang hoạt động (không bị khóa), chưa có đơn giữ chỗ ở khung đó. Admin không bao giờ được tính. Tự gán người ít đơn nhất trong ngày. Đơn Chờ duyệt quá giờ hẹn tự hủy "Hết hạn" (không cần cron).

## 4. Phân quyền

- Bảng quyền tập trung `config/permissions.php`, kiểm tra bằng `Auth::can()`.
- Middleware theo nhóm route (`config/routes.php`): `/admin/*` chỉ admin, `/staff/*` chỉ staff, trang khách chỉ client.
- `BookingPolicy` kiểm tra theo bản ghi (chống IDOR): khách chỉ thấy đơn của mình, nhân viên chỉ thấy đơn được giao (đơn người khác trả 404).
- Mỗi request đọc lại tài khoản từ database: khóa là bị đăng xuất ngay, đổi vai trò có hiệu lực ngay. Mật khẩu tạm bắt đổi trước mọi trang.
- Bảo mật: PDO prepared statements, `password_hash`, `htmlspecialchars`, CSRF cho mọi POST, `session_regenerate_id`, cookie HttpOnly + SameSite=Lax, upload kiểm tra MIME thật bằng `finfo`, chặn thực thi PHP trong thư mục upload, giới hạn 5 lần đăng nhập sai.

## 5. Kiểm thử

```bash
php tests/run.php
```
34 kịch bản (dùng SQLite tạm, không đụng database MySQL): các kịch bản trùng lịch trong đề cương (gồm mô phỏng tranh chấp lọt qua server và bị chỉ mục duy nhất chặn), quy tắc đặt lịch, phân quyền, quản lý người dùng, ảnh trực tiếp (ai được gửi, ai được xem, chặn file giả ảnh).

## 6. Cấu trúc thư mục

```
public/            index.php (front controller), assets/, uploads/pets/
core/              Router, Database, Controller, View, Session, Csrf, Auth, helpers
config/            config.php, permissions.php, routes.php
app/Middleware/    Auth, Guest, Role, Permission
app/Policies/      BookingPolicy
app/Services/      BookingService, BookingPhotoService, UserService, ActivityLogger, PhotoUploader
app/Models/        model cho 13 bảng (BookingPhoto: ảnh ca làm)
app/Controllers/   Home, Auth, Account, Pet, Booking, Photo, Api/, Staff/, Admin/
app/Views/         layouts/, partials/, client/, staff/, admin/
database/          schema.sql (MySQL), schema.sqlite.sql (chỉ cho test), install.php, seed.php
storage/booking_photos/  ảnh ca làm (ngoài public, chỉ xem qua /photos/{id} có kiểm tra quyền)
tests/run.php      kiểm thử nghiệp vụ
```

Phân công 4 phần (chức năng, file, bảng, nhánh Git của từng người): xem [PHAN_CONG.md](PHAN_CONG.md).

## 7. Ghi chú

- **Hai giao diện riêng:**
  - **Khách hàng** (`layouts/main.php`): nền kem, cam và xanh lá, font Be Vietnam Pro, nhiều ảnh thú cưng, thanh điều hướng dưới đáy trên điện thoại.
  - **Quản trị và nhân viên** (`layouts/panel.php`, lớp `admin-theme`): nền trắng, sidebar sáng, cam và xanh bạc hà, font Lexend, dạng bảng điều khiển.
  - Màu của cả hai khai báo bằng biến CSS trong `partials/head.php`.
- **Ảnh thú cưng:** mặc định lấy ảnh miễn phí từ Unsplash (cần Internet). Để dùng ảnh của nhóm, tạo thư mục `public/assets/img/photos/` và bỏ ảnh vào theo đúng tên khóa trong `PHOTO_SOURCES` ở `core/helpers.php` (`hero`, `bath`, `groom`, `walk`, `spa`, `care`, `cat`, `cat2`, `puppy`, `camera`, `login`; đuôi `.jpg`, `.png` hoặc `.webp`, ví dụ `hero.jpg`). Khi mất mạng, trang tự dùng hình vẽ dự phòng.

- **Ảnh cập nhật trực tiếp** (thay cho camera mô phỏng):
  - Bảng `booking_photos` (đơn, người chụp, mốc, ghi chú, file). File nằm trong `storage/booking_photos/`, ngoài thư mục `public`, nên không ai mở thẳng bằng đường dẫn. Mọi lượt xem đi qua `PhotoController` và `BookingPolicy::view`: chủ đơn, nhân viên được giao, Admin; người khác nhận 404.
  - Chỉ nhân viên phụ trách gửi được, và chỉ khi đơn *Đang tiến hành* (Admin gửi bổ sung được cả khi đã hoàn thành). Kiểm tra ảnh thật bằng `finfo` + `getimagesize`, đổi tên ngẫu nhiên, mỗi đơn tối đa 40 ảnh, có ghi nhật ký.
  - Trình duyệt thu nhỏ ảnh về 1600px trước khi gửi. Nếu PHP bật `gd`, máy chủ còn xoay đúng chiều ảnh chụp dọc và tạo ảnh nhỏ 480px cho dòng thời gian; không có `gd` vẫn chạy, chỉ giữ nguyên ảnh.
  - Trang khách hỏi `GET /bookings/{id}/photos?after=<id ảnh cuối>` mỗi 5 giây (đổi ở `config.php`, mục `booking_photo.poll_seconds`), tạm dừng khi tab bị ẩn và dừng hẳn khi đơn hoàn thành. Không cần HTTPS hay máy chủ video.
  - **Đã cài bản cũ?** Bản này thêm bảng mới nên cần chạy lại `php database/install.php` (làm mới dữ liệu mẫu). Muốn giữ dữ liệu đang có thì chỉ chạy đoạn `CREATE TABLE booking_photos (...)` ở cuối `database/schema.sql` trong tab SQL của phpMyAdmin.
- **Múi giờ:** Asia/Ho_Chi_Minh cho cả PHP và MySQL (`SET time_zone = '+07:00'`).
- Tắt hiển thị lỗi khi nộp/demo công khai: đặt `'debug' => false` trong `config/config.local.php`.
