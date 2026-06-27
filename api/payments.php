<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();

$bookingId = (int)($_POST['booking_id'] ?? 0);
$amount    = (float)($_POST['amount'] ?? 0);
$method    = $_POST['method'] ?? 'cash';
$reference = trim($_POST['reference'] ?? '');

if (!$bookingId || $amount <= 0) {
    flash('danger', 'Invalid payment data.');
    header('Location: ' . BASE_URL . '/modules/bookings/view.php?id=' . $bookingId);
    exit;
}

Database::execute("INSERT INTO payments (booking_id, amount, method, status, reference, paid_at, created_by)
    VALUES (?, ?, ?, 'completed', ?, NOW(), ?)",
    [$bookingId, $amount, $method, $reference ?: null, Auth::id()]);

// Update booking total_amount to reflect payment
$paid = Database::queryOne("SELECT COALESCE(SUM(amount),0) t FROM payments WHERE booking_id=? AND status='completed'", [$bookingId])['t'] ?? 0;

flash('success', 'Payment of ' . number_format($amount, 2) . ' recorded.');
header('Location: ' . BASE_URL . '/modules/bookings/view.php?id=' . $bookingId);
exit;
