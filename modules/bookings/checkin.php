<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
Auth::require();

$id = (int)($_GET['id'] ?? 0);
$booking = Database::queryOne("SELECT * FROM bookings WHERE id=? AND status='confirmed'", [$id]);
if (!$booking) { redirect('/modules/bookings/'); }

Database::beginTransaction();
try {
    Database::execute("UPDATE bookings SET status='checked_in' WHERE id=?", [$id]);
    Database::execute("UPDATE rooms SET status='occupied' WHERE id=?", [$booking['room_id']]);
    Database::commit();
    flash('success', "Guest checked in to room successfully.");
} catch (Exception $e) {
    Database::rollback();
    flash('danger', 'Check-in failed: ' . $e->getMessage());
}
redirect("/modules/bookings/view.php?id={$id}");
