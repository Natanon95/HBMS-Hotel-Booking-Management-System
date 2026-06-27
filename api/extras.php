<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();

$bookingId  = (int)($_POST['booking_id'] ?? 0);
$name       = trim($_POST['name'] ?? '');
$qty        = max(1, (int)($_POST['qty'] ?? 1));
$unitPrice  = (float)($_POST['unit_price'] ?? 0);
$total      = $qty * $unitPrice;

if (!$bookingId || !$name) {
    flash('danger', 'Invalid extra data.');
    header('Location: ' . BASE_URL . '/modules/bookings/view.php?id=' . $bookingId);
    exit;
}

Database::execute("INSERT INTO booking_extras (booking_id, name, qty, unit_price, total_price, extra_date)
    VALUES (?, ?, ?, ?, ?, CURDATE())",
    [$bookingId, $name, $qty, $unitPrice, $total]);

// Update booking total_amount
$roomTotal  = Database::queryOne("SELECT room_rate, check_in, check_out FROM bookings WHERE id=?", [$bookingId]);
$extras     = Database::queryOne("SELECT COALESCE(SUM(total_price),0) t FROM booking_extras WHERE booking_id=?", [$bookingId])['t'] ?? 0;
$n          = (int)((new DateTime($roomTotal['check_out']))->diff(new DateTime($roomTotal['check_in']))->days);
$newTotal   = ($roomTotal['room_rate'] * max(1, $n)) + $extras;
Database::execute("UPDATE bookings SET total_amount=? WHERE id=?", [$newTotal, $bookingId]);

flash('success', "Extra '" . e($name) . "' added.");
header('Location: ' . BASE_URL . '/modules/bookings/view.php?id=' . $bookingId);
exit;
