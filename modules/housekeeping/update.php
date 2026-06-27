<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
Auth::require();

$id     = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$allowed = ['pending','in_progress','done','skipped'];

if ($id && in_array($status, $allowed)) {
    $task = Database::queryOne("SELECT * FROM housekeeping WHERE id=?", [$id]);
    if ($task) {
        Database::execute("UPDATE housekeeping SET status=?, completed_at=? WHERE id=?",
            [$status, $status === 'done' ? date('Y-m-d H:i:s') : null, $id]);
        // If done, mark room available
        if ($status === 'done') {
            $room = Database::queryOne("SELECT status FROM rooms WHERE id=?", [$task['room_id']]);
            if ($room && $room['status'] === 'cleaning') {
                Database::execute("UPDATE rooms SET status='available' WHERE id=?", [$task['room_id']]);
            }
        }
        flash('success', "Task marked as {$status}.");
    }
}
redirect("/modules/housekeeping/?date=" . date('Y-m-d'));
