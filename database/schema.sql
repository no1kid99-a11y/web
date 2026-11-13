-- =====================================================================
-- PetCare Home - Website chăm sóc thú cưng tại nhà
-- Schema MySQL 5.7+ / MariaDB 10.2.5+ (InnoDB, utf8mb4)
-- 13 bảng nghiệp vụ, 2 quan hệ N-N (combo_service, booking_services)
-- =====================================================================
SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS booking_photos, activity_logs, staff_leaves, reviews, booking_services, bookings,
    time_slots, combo_service, combos, services, health_records, vaccinations, pets, users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tài khoản: Client, Nhân viên, Admin (mỗi tài khoản đúng một vai trò)
CREATE TABLE users (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(100)  NOT NULL,
    email                VARCHAR(150)  NOT NULL,
    password             VARCHAR(255)  NOT NULL,
    phone                VARCHAR(20)   NOT NULL,
    address              VARCHAR(255)  NULL,
    role                 ENUM('client','staff','admin') NOT NULL DEFAULT 'client',
    status               ENUM('active','locked')        NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1)    NOT NULL DEFAULT 0,
    last_login_at        DATETIME      NULL,
    created_by           INT UNSIGNED  NULL,
    created_at           DATETIME      NOT NULL,
    updated_at           DATETIME      NULL,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status),
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Hồ sơ thú cưng (xóa mềm bằng is_active)
CREATE TABLE pets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(100) NOT NULL,
    species    ENUM('dog','cat','other') NOT NULL,
    breed      VARCHAR(100) NULL,
    age        TINYINT UNSIGNED NULL,
    weight     DECIMAL(5,2) NULL,
    photo      VARCHAR(255) NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL,
    updated_at DATETIME     NULL,
    KEY idx_pets_user (user_id, is_active),
    CONSTRAINT fk_pets_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Lịch sử tiêm phòng
CREATE TABLE vaccinations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id        INT UNSIGNED NOT NULL,
    vaccine_name  VARCHAR(150) NOT NULL,
    vaccinated_at DATE NOT NULL,
    next_due_at   DATE NULL,
    created_at    DATETIME NOT NULL,
    KEY idx_vacc_pet (pet_id),
    CONSTRAINT fk_vacc_pet FOREIGN KEY (pet_id) REFERENCES pets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Dịch vụ lẻ
CREATE TABLE services (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(150) NOT NULL,
    description      TEXT NULL,
    price            INT UNSIGNED NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    is_active        TINYINT(1) NOT NULL DEFAULT 1,
    created_at       DATETIME NOT NULL,
    updated_at       DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Gói combo
CREATE TABLE combos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price       INT UNSIGNED NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng trung gian combo <-> dịch vụ (N-N)
CREATE TABLE combo_service (
    combo_id   INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (combo_id, service_id),
    CONSTRAINT fk_cs_combo   FOREIGN KEY (combo_id)   REFERENCES combos(id) ON DELETE CASCADE,
    CONSTRAINT fk_cs_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Khung giờ cố định trong ngày
CREATE TABLE time_slots (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    start_time TIME NOT NULL,
    end_time   TIME NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_slot_start (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Lịch hẹn
CREATE TABLE bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    pet_id          INT UNSIGNED NOT NULL,
    combo_id        INT UNSIGNED NULL,
    time_slot_id    INT UNSIGNED NOT NULL,
    -- Bắt buộc NOT NULL: MySQL cho phép nhiều NULL trong chỉ mục duy nhất,
    -- nếu staff_id NULL thì hai đơn cùng khung vẫn lọt qua uq_staff_slot.
    staff_id        INT UNSIGNED NOT NULL,
    booking_date    DATE NOT NULL,
    address         VARCHAR(255) NOT NULL,
    total_price     INT UNSIGNED NOT NULL,
    discount_amount INT UNSIGNED NOT NULL DEFAULT 0,
    status          ENUM('pending','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    note            TEXT NULL,
    cancel_reason   VARCHAR(255) NULL,
    cancelled_by    INT UNSIGNED NULL,
    -- Cột sinh tự động: 1 khi đơn đang giữ chỗ, NULL khi hoàn thành/đã hủy (không chặn ai).
    active_flag     TINYINT(1) AS (CASE WHEN status IN ('pending','confirmed','in_progress') THEN 1 ELSE NULL END) STORED,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NULL,
    UNIQUE KEY uq_staff_slot (staff_id, booking_date, time_slot_id, active_flag),
    UNIQUE KEY uq_pet_slot   (pet_id,   booking_date, time_slot_id, active_flag),
    KEY idx_bookings_date_status (booking_date, status),
    KEY idx_bookings_user (user_id),
    CONSTRAINT fk_b_user  FOREIGN KEY (user_id)      REFERENCES users(id),
    CONSTRAINT fk_b_pet   FOREIGN KEY (pet_id)       REFERENCES pets(id),
    CONSTRAINT fk_b_combo FOREIGN KEY (combo_id)     REFERENCES combos(id),
    CONSTRAINT fk_b_slot  FOREIGN KEY (time_slot_id) REFERENCES time_slots(id),
    CONSTRAINT fk_b_staff FOREIGN KEY (staff_id)     REFERENCES users(id),
    CONSTRAINT fk_b_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng trung gian lịch hẹn <-> dịch vụ (N-N), lưu giá lẻ tại thời điểm đặt
CREATE TABLE booking_services (
    booking_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    price      INT UNSIGNED NOT NULL,
    PRIMARY KEY (booking_id, service_id),
    CONSTRAINT fk_bs_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_bs_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Sổ sức khỏe sau mỗi lần chăm sóc
CREATE TABLE health_records (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id      INT UNSIGNED NOT NULL,
    booking_id  INT UNSIGNED NULL,
    recorded_by INT UNSIGNED NOT NULL,
    weight      DECIMAL(5,2) NULL,
    note        TEXT NOT NULL,
    recorded_at DATETIME NOT NULL,
    KEY idx_hr_pet (pet_id),
    KEY idx_hr_booking (booking_id),
    CONSTRAINT fk_hr_pet     FOREIGN KEY (pet_id)      REFERENCES pets(id),
    CONSTRAINT fk_hr_booking FOREIGN KEY (booking_id)  REFERENCES bookings(id),
    CONSTRAINT fk_hr_user    FOREIGN KEY (recorded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Đánh giá (mỗi đơn một lần)
CREATE TABLE reviews (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    rating     TINYINT UNSIGNED NOT NULL,
    comment    TEXT NULL,
    is_hidden  TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uq_reviews_booking (booking_id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_r_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
    CONSTRAINT fk_r_user    FOREIGN KEY (user_id)    REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Nhật ký thao tác của Admin và Nhân viên
CREATE TABLE activity_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(50)  NOT NULL,
    target_type VARCHAR(30)  NOT NULL,
    target_id   INT UNSIGNED NULL,
    detail      TEXT NULL,
    ip          VARCHAR(45)  NULL,
    created_at  DATETIME     NOT NULL,
    KEY idx_logs_created (created_at),
    KEY idx_logs_target (target_type, target_id),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Ảnh cập nhật trong ca làm: nhân viên chụp theo từng mốc, khách xem gần như trực tiếp.
--     File ảnh nằm ngoài thư mục public (storage/booking_photos), chỉ tải qua route có kiểm tra quyền.
CREATE TABLE booking_photos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    file        VARCHAR(64)  NOT NULL,
    thumb       VARCHAR(64)  NULL,
    stage       VARCHAR(20)  NOT NULL DEFAULT 'other',
    caption     VARCHAR(255) NULL,
    created_at  DATETIME     NOT NULL,
    KEY idx_bp_booking (booking_id, id),
    CONSTRAINT fk_bp_booking FOREIGN KEY (booking_id)  REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_bp_user    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
