<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
Auth::require();

$id = (int)($_GET['id'] ?? 0);
$booking = Database::queryOne("SELECT * FROM bookings WHERE id=? AND status='checked_in'", [$id]);
if (!$booking) { redirect('/modules/bookings/'); }

Database::beginTransaction();
try {
    Database::execute("UPDATE bookings SET status='checked_out' WHERE id=?", [$id]);
    Database::execute("UPDATE rooms SET status='cleaning' WHERE id=?", [$booking['room_id']]);
    // Auto-create housekeeping task
    Database::execute("
        INSERT INTO housekeeping (room_id, booking_id, task_type, status, scheduled_date)
        VALUES (?, ?, 'checkout_clean', 'pending', CURDATE())",
        [$booking['room_id'], $id]);
    Database::commit();
    flash('success', "Guest checked out. Room queued for cleaning.");
} catch (Exception $e) {
    Database::rollback();
    flash('danger', 'Check-out failed: ' . $e->getMessage());
}
redirect("/modules/bookings/view.php?id={$id}");
