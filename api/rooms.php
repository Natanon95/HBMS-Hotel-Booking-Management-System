<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
Auth::requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();

$action = $_POST['action'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/modules/rooms/';

if ($action === 'create') {
    $num    = trim($_POST['room_number'] ?? '');
    $typeId = (int)($_POST['room_type_id'] ?? 0);
    $floor  = max(1, (int)($_POST['floor'] ?? 1));
    if ($num && $typeId) {
        try {
            Database::execute("INSERT INTO rooms (room_number, room_type_id, floor) VALUES (?,?,?)", [$num, $typeId, $floor]);
            flash('success', "Room {$num} added.");
        } catch (Exception) { flash('danger', 'Room number already exists.'); }
    }
} elseif ($action === 'status') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $notes  = trim($_POST['notes'] ?? '');
    $allowed = ['available','cleaning','maintenance','out_of_order'];
    if ($roomId && in_array($status, $allowed)) {
        Database::execute("UPDATE rooms SET status=?, notes=? WHERE id=?", [$status, $notes, $roomId]);
        flash('success', 'Room status updated.');
    }
}

header('Location: ' . $referer);
exit;
