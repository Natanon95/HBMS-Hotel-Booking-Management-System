<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
Auth::require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();

$action = $_POST['action'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/modules/housekeeping/';

if ($action === 'create') {
    $roomId   = (int)($_POST['room_id'] ?? 0);
    $taskType = $_POST['task_type'] ?? 'daily_clean';
    $date     = $_POST['scheduled_date'] ?? date('Y-m-d');
    $notes    = trim($_POST['notes'] ?? '');
    if ($roomId) {
        Database::execute("INSERT INTO housekeeping (room_id, task_type, status, scheduled_date, notes) VALUES (?,?,'pending',?,?)",
            [$roomId, $taskType, $date, $notes ?: null]);
        flash('success', 'Task added.');
    }
}

header('Location: ' . $referer);
exit;
