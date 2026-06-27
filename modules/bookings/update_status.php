<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
Auth::require();

$id     = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$allowed = ['pending','confirmed'];
if ($id && in_array($status, $allowed)) {
    Database::execute("UPDATE bookings SET status=? WHERE id=?", [$status, $id]);
    flash('success', "Booking status updated to {$status}.");
}
redirect("/modules/bookings/view.php?id={$id}");
