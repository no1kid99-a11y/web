-- Bản SQLite của schema.sql, CHỈ dùng cho kiểm thử tự động (tests/run.php).
-- Ứng dụng thật chạy trên MySQL/MariaDB với database/schema.sql.
PRAGMA foreign_keys = OFF;
DROP TABLE IF EXISTS booking_photos; DROP TABLE IF EXISTS activity_logs; DROP TABLE IF EXISTS staff_leaves; DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS booking_services; DROP TABLE IF EXISTS bookings; DROP TABLE IF EXISTS time_slots;
DROP TABLE IF EXISTS combo_service; DROP TABLE IF EXISTS combos; DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS health_records; DROP TABLE IF EXISTS vaccinations; DROP TABLE IF EXISTS pets;
DROP TABLE IF EXISTS users;
PRAGMA foreign_keys = ON;

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, phone TEXT NOT NULL, address TEXT,
    role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client','staff','admin')),
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','locked')),
    must_change_password INTEGER NOT NULL DEFAULT 0, last_login_at TEXT,
    created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TEXT NOT NULL, updated_at TEXT
);
CREATE TABLE pets (
    id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL REFERENCES users(id),
    name TEXT NOT NULL, species TEXT NOT NULL CHECK (species IN ('dog','cat','other')), breed TEXT,
    age INTEGER, weight REAL, photo TEXT, is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL, updated_at TEXT
);
CREATE TABLE vaccinations (
    id INTEGER PRIMARY KEY AUTOINCREMENT, pet_id INTEGER NOT NULL REFERENCES pets(id),
    vaccine_name TEXT NOT NULL, vaccinated_at TEXT NOT NULL, next_due_at TEXT, created_at TEXT NOT NULL
);
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, description TEXT, price INTEGER NOT NULL,
    duration_minutes INTEGER NOT NULL, is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL, updated_at TEXT
);
CREATE TABLE combos (
    id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, description TEXT, price INTEGER NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1, created_at TEXT NOT NULL, updated_at TEXT
);
CREATE TABLE combo_service (
    combo_id INTEGER NOT NULL REFERENCES combos(id) ON DELETE CASCADE,
    service_id INTEGER NOT NULL REFERENCES services(id),
    PRIMARY KEY (combo_id, service_id)
);
CREATE TABLE time_slots (
    id INTEGER PRIMARY KEY AUTOINCREMENT, start_time TEXT NOT NULL UNIQUE, end_time TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL REFERENCES users(id), pet_id INTEGER NOT NULL REFERENCES pets(id),
    combo_id INTEGER REFERENCES combos(id), time_slot_id INTEGER NOT NULL REFERENCES time_slots(id),
    staff_id INTEGER NOT NULL REFERENCES users(id), booking_date TEXT NOT NULL, address TEXT NOT NULL,
    total_price INTEGER NOT NULL, discount_amount INTEGER NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','confirmed','in_progress','completed','cancelled')),
    note TEXT, cancel_reason TEXT, cancelled_by INTEGER REFERENCES users(id),
    active_flag INTEGER GENERATED ALWAYS AS (CASE WHEN status IN ('pending','confirmed','in_progress') THEN 1 ELSE NULL END) STORED,
    created_at TEXT NOT NULL, updated_at TEXT
);
CREATE UNIQUE INDEX uq_staff_slot ON bookings (staff_id, booking_date, time_slot_id, active_flag);
CREATE UNIQUE INDEX uq_pet_slot ON bookings (pet_id, booking_date, time_slot_id, active_flag);
CREATE INDEX idx_bookings_date_status ON bookings (booking_date, status);
CREATE TABLE booking_services (
    booking_id INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    service_id INTEGER NOT NULL REFERENCES services(id), price INTEGER NOT NULL,
    PRIMARY KEY (booking_id, service_id)
);
CREATE TABLE health_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT, pet_id INTEGER NOT NULL REFERENCES pets(id),
    booking_id INTEGER REFERENCES bookings(id), recorded_by INTEGER NOT NULL REFERENCES users(id),
    weight REAL, note TEXT NOT NULL, recorded_at TEXT NOT NULL
);
CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT, booking_id INTEGER NOT NULL UNIQUE REFERENCES bookings(id),
    user_id INTEGER NOT NULL REFERENCES users(id), rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT, is_hidden INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL
);
CREATE TABLE activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER REFERENCES users(id), action TEXT NOT NULL,
    target_type TEXT NOT NULL, target_id INTEGER, detail TEXT, ip TEXT, created_at TEXT NOT NULL
);
CREATE TABLE booking_photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    booking_id INTEGER NOT NULL REFERENCES bookings(id) ON DELETE CASCADE,
    uploaded_by INTEGER NOT NULL REFERENCES users(id),
    file TEXT NOT NULL, thumb TEXT, stage TEXT NOT NULL DEFAULT 'other', caption TEXT, created_at TEXT NOT NULL
);
CREATE INDEX idx_bp_booking ON booking_photos (booking_id, id);
