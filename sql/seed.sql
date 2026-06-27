-- Hotel Booking System Seed Data (Demo)
-- Run schema.sql first

-- Users (password = "password" hashed with bcrypt)
INSERT INTO users (name, email, password, role) VALUES
('Admin User',        'admin@hotel.demo',       '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Sara Receptionist', 'sara@hotel.demo',         '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist'),
('Tom Night Shift',   'tom@hotel.demo',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist');

-- Room types
INSERT INTO room_types (name, description, base_price, max_occupancy, amenities) VALUES
('Standard Single',  'Cozy single room with city view',                  1200.00, 1, '["WiFi","TV","Air conditioning","Minibar"]'),
('Standard Double',  'Comfortable double room with garden view',          1800.00, 2, '["WiFi","TV","Air conditioning","Minibar","Bathtub"]'),
('Deluxe Double',    'Spacious deluxe room with pool view',               2800.00, 2, '["WiFi","TV","Air conditioning","Minibar","Bathtub","Balcony"]'),
('Junior Suite',     'Elegant suite with living area and ocean view',     4500.00, 3, '["WiFi","Smart TV","Air conditioning","Minibar","Jacuzzi","Balcony","Sofa bed"]'),
('Executive Suite',  'Luxurious suite with panoramic view and butler',    8000.00, 4, '["WiFi","Smart TV","Air conditioning","Minibar","Jacuzzi","Balcony","Sofa bed","Butler service","Lounge access"]');

-- Rooms
INSERT INTO rooms (room_number, room_type_id, floor, status) VALUES
('101', 1, 1, 'available'), ('102', 1, 1, 'available'), ('103', 1, 1, 'cleaning'),
('104', 2, 1, 'available'), ('105', 2, 1, 'occupied'),  ('106', 2, 1, 'available'),
('201', 2, 2, 'available'), ('202', 3, 2, 'available'), ('203', 3, 2, 'occupied'),
('204', 3, 2, 'maintenance'),('205', 3, 2, 'available'),
('301', 4, 3, 'available'), ('302', 4, 3, 'occupied'),  ('303', 4, 3, 'available'),
('401', 5, 4, 'available'), ('402', 5, 4, 'occupied');

-- Guests
INSERT INTO guests (first_name, last_name, email, phone, id_type, id_number, nationality) VALUES
('Somchai',   'Jaidee',      'somchai@email.com',   '081-234-5678', 'national_id', '1100100234561', 'Thai'),
('Pranee',    'Suksaeng',    'pranee@email.com',    '082-345-6789', 'national_id', '1100200345672', 'Thai'),
('James',     'Wilson',      'james.w@email.com',   '+1-555-0123',  'passport',    'US123456789',   'American'),
('Yuki',      'Tanaka',      'yuki.t@email.com',    '+81-90-1234',  'passport',    'JP987654321',   'Japanese'),
('Mohammed',  'Al-Rashid',   'm.rashid@email.com',  '+971-50-111',  'passport',    'AE456789012',   'Emirati'),
('Sophie',    'Dupont',      'sophie.d@email.com',  '+33-6-1234',   'passport',    'FR789012345',   'French'),
('Chen',      'Wei',         'chen.wei@email.com',  '+86-138-0000', 'passport',    'CN234567890',   'Chinese'),
('Anna',      'Müller',      'anna.m@email.com',    '+49-170-111',  'passport',    'DE567890123',   'German'),
('Kasem',     'Burana',      'kasem@email.com',     '083-456-7890', 'national_id', '1100300456783', 'Thai'),
('Malee',     'Thongdee',    'malee@email.com',     '084-567-8901', 'national_id', '1100400567894', 'Thai');

-- Bookings (mix of statuses relative to today)
SET @today = CURDATE();

INSERT INTO bookings (booking_ref, guest_id, room_id, check_in, check_out, adults, children, status, room_rate, total_amount, source, created_by) VALUES
-- Active / Current
('BK-2026-001', 1, 5,  DATE_SUB(@today,INTERVAL 1 DAY), DATE_ADD(@today,INTERVAL 2 DAY), 2,0,'checked_in',  1800.00, 5400.00, 'walk_in', 2),
('BK-2026-002', 3, 9,  DATE_SUB(@today,INTERVAL 2 DAY), DATE_ADD(@today,INTERVAL 1 DAY), 2,1,'checked_in',  2800.00, 8400.00, 'online',  1),
('BK-2026-003', 5, 13, DATE_SUB(@today,INTERVAL 3 DAY), DATE_ADD(@today,INTERVAL 4 DAY), 2,0,'checked_in',  4500.00,31500.00, 'agent',   1),
('BK-2026-004', 7, 16, DATE_SUB(@today,INTERVAL 1 DAY), DATE_ADD(@today,INTERVAL 5 DAY), 2,2,'checked_in',  8000.00,48000.00, 'online',  1),
-- Today's arrivals (confirmed)
('BK-2026-005', 2, 1,  @today, DATE_ADD(@today,INTERVAL 3 DAY), 1,0,'confirmed', 1200.00, 3600.00, 'phone',   2),
('BK-2026-006', 4, 4,  @today, DATE_ADD(@today,INTERVAL 2 DAY), 2,0,'confirmed', 1800.00, 3600.00, 'online',  2),
('BK-2026-007', 6, 8,  @today, DATE_ADD(@today,INTERVAL 4 DAY), 2,0,'confirmed', 2800.00,11200.00, 'agent',   1),
-- Upcoming
('BK-2026-008', 8,  2, DATE_ADD(@today,INTERVAL 1 DAY), DATE_ADD(@today,INTERVAL 3 DAY), 1,0,'confirmed', 1200.00, 2400.00, 'walk_in', 2),
('BK-2026-009', 9,  6, DATE_ADD(@today,INTERVAL 2 DAY), DATE_ADD(@today,INTERVAL 5 DAY), 2,0,'confirmed', 1800.00, 5400.00, 'phone',   2),
('BK-2026-010', 10,11, DATE_ADD(@today,INTERVAL 3 DAY), DATE_ADD(@today,INTERVAL 6 DAY), 2,1,'pending',   2800.00, 8400.00, 'online',  1),
-- Past
('BK-2025-001', 1, 1,  DATE_SUB(@today,INTERVAL 10 DAY), DATE_SUB(@today,INTERVAL 7 DAY), 1,0,'checked_out',1200.00, 3600.00, 'walk_in', 2),
('BK-2025-002', 2, 4,  DATE_SUB(@today,INTERVAL 8  DAY), DATE_SUB(@today,INTERVAL 5 DAY), 2,0,'checked_out',1800.00, 5400.00, 'online',  1),
('BK-2025-003', 3, 8,  DATE_SUB(@today,INTERVAL 15 DAY), DATE_SUB(@today,INTERVAL 10 DAY),2,1,'checked_out',2800.00,14000.00, 'agent',   1),
('BK-2025-004', 4, 12, DATE_SUB(@today,INTERVAL 5  DAY), DATE_SUB(@today,INTERVAL 2 DAY), 2,0,'checked_out',4500.00,13500.00, 'phone',   2),
('BK-2025-005', 5, 15, DATE_SUB(@today,INTERVAL 12 DAY), DATE_SUB(@today,INTERVAL 8 DAY), 2,0,'cancelled',  8000.00,    0.00, 'online',  1);

-- Payments
INSERT INTO payments (booking_id, amount, method, status, paid_at, created_by) VALUES
(1,  5400.00, 'cash',          'completed', NOW(), 2),
(2,  8400.00, 'credit_card',   'completed', NOW(), 1),
(3, 31500.00, 'bank_transfer', 'completed', NOW(), 1),
(4, 48000.00, 'credit_card',   'completed', NOW(), 1),
(5,  1800.00, 'cash',          'completed', NOW(), 2),  -- deposit
(11, 3600.00, 'cash',          'completed', DATE_SUB(NOW(),INTERVAL 7 DAY),  2),
(12, 5400.00, 'credit_card',   'completed', DATE_SUB(NOW(),INTERVAL 5 DAY),  1),
(13,14000.00, 'credit_card',   'completed', DATE_SUB(NOW(),INTERVAL 10 DAY), 1),
(14,13500.00, 'bank_transfer', 'completed', DATE_SUB(NOW(),INTERVAL 2 DAY),  2);

-- Booking extras
INSERT INTO booking_extras (booking_id, name, qty, unit_price, total_price, extra_date) VALUES
(1, 'Breakfast (x2)',  2, 250.00,  500.00, @today),
(2, 'Airport Transfer',1, 800.00,  800.00, DATE_SUB(@today,INTERVAL 2 DAY)),
(3, 'Half-board (x2)', 2, 1200.00,2400.00, @today),
(3, 'City Tour',       2,  900.00,1800.00, DATE_ADD(@today,INTERVAL 1 DAY)),
(4, 'Full-board (x4)', 4, 1500.00,6000.00, @today),
(4, 'Spa Package',     2, 2200.00,4400.00, DATE_ADD(@today,INTERVAL 2 DAY));

-- Housekeeping tasks
INSERT INTO housekeeping (room_id, booking_id, task_type, status, assigned_to, scheduled_date) VALUES
(3,  NULL, 'checkout_clean', 'pending',     NULL, @today),
(5,  1,    'daily_clean',    'in_progress', NULL, @today),
(9,  2,    'daily_clean',    'done',        NULL, @today),
(13, 3,    'daily_clean',    'pending',     NULL, @today),
(16, 4,    'daily_clean',    'pending',     NULL, @today),
(10, NULL, 'maintenance',    'pending',     NULL, @today),
(1,  5,    'checkout_clean', 'pending',     NULL, DATE_ADD(@today,INTERVAL 3 DAY)),
(4,  6,    'checkout_clean', 'pending',     NULL, DATE_ADD(@today,INTERVAL 2 DAY));
