-- Hotel Booking System Schema
-- MySQL 8.0+

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS housekeeping;
DROP TABLE IF EXISTS booking_extras;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_types;
DROP TABLE IF EXISTS guests;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users (staff)
CREATE TABLE users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','receptionist') NOT NULL DEFAULT 'receptionist',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    last_login  DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Guests
CREATE TABLE guests (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name   VARCHAR(80) NOT NULL,
    last_name    VARCHAR(80) NOT NULL,
    email        VARCHAR(150) NULL,
    phone        VARCHAR(30) NULL,
    id_type      ENUM('passport','national_id','driver_license','other') NOT NULL DEFAULT 'national_id',
    id_number    VARCHAR(50) NULL,
    nationality  VARCHAR(60) NULL,
    address      TEXT NULL,
    notes        TEXT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_name  (last_name, first_name)
);

-- Room types
CREATE TABLE room_types (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(80) NOT NULL,
    description     TEXT NULL,
    base_price      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_occupancy   TINYINT UNSIGNED NOT NULL DEFAULT 2,
    amenities       JSON NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Rooms
CREATE TABLE rooms (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number     VARCHAR(10) NOT NULL UNIQUE,
    room_type_id    INT UNSIGNED NOT NULL,
    floor           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status          ENUM('available','occupied','cleaning','maintenance','out_of_order') NOT NULL DEFAULT 'available',
    notes           TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rooms_type FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Bookings
CREATE TABLE bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ref     VARCHAR(12) NOT NULL UNIQUE,
    guest_id        INT UNSIGNED NOT NULL,
    room_id         INT UNSIGNED NOT NULL,
    check_in        DATE NOT NULL,
    check_out       DATE NOT NULL,
    adults          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    children        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status          ENUM('pending','confirmed','checked_in','checked_out','cancelled','no_show') NOT NULL DEFAULT 'pending',
    room_rate       DECIMAL(10,2) NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    special_requests TEXT NULL,
    source          ENUM('walk_in','phone','online','agent') NOT NULL DEFAULT 'walk_in',
    created_by      INT UNSIGNED NULL,
    cancelled_at    DATETIME NULL,
    cancel_reason   TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_guest   FOREIGN KEY (guest_id)    REFERENCES guests(id),
    CONSTRAINT fk_bookings_room    FOREIGN KEY (room_id)     REFERENCES rooms(id),
    CONSTRAINT fk_bookings_user    FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_check_in  (check_in),
    INDEX idx_check_out (check_out),
    INDEX idx_status    (status)
);

-- Payments
CREATE TABLE payments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT UNSIGNED NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    method          ENUM('cash','credit_card','debit_card','bank_transfer','online') NOT NULL DEFAULT 'cash',
    status          ENUM('pending','completed','refunded','failed') NOT NULL DEFAULT 'pending',
    reference       VARCHAR(100) NULL,
    notes           TEXT NULL,
    paid_at         DATETIME NULL,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)  REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_user    FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL
);

-- Booking extras (meals, tours, services)
CREATE TABLE booking_extras (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    qty         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price  DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    extra_date  DATE NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_extras_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Housekeeping tasks
CREATE TABLE housekeeping (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id     INT UNSIGNED NOT NULL,
    booking_id  INT UNSIGNED NULL,
    task_type   ENUM('checkout_clean','daily_clean','deep_clean','maintenance','inspection') NOT NULL DEFAULT 'daily_clean',
    status      ENUM('pending','in_progress','done','skipped') NOT NULL DEFAULT 'pending',
    assigned_to INT UNSIGNED NULL,
    notes       TEXT NULL,
    scheduled_date DATE NOT NULL,
    completed_at   DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_hk_room    FOREIGN KEY (room_id)     REFERENCES rooms(id),
    CONSTRAINT fk_hk_booking FOREIGN KEY (booking_id)  REFERENCES bookings(id) ON DELETE SET NULL,
    CONSTRAINT fk_hk_user    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_hk_date   (scheduled_date),
    INDEX idx_hk_status (status)
);
