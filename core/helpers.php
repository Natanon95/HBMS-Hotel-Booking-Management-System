<?php

function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatMoney(float $amount, string $symbol = '฿'): string {
    return $symbol . number_format($amount, 2);
}

function formatDate(string $date, string $format = 'd M Y'): string {
    return $date ? date($format, strtotime($date)) : '-';
}

function formatDateTime(string $dt, string $format = 'd M Y H:i'): string {
    return $dt ? date($format, strtotime($dt)) : '-';
}

function nights(string $checkIn, string $checkOut): int {
    $diff = (new DateTime($checkOut))->diff(new DateTime($checkIn));
    return max(1, (int)$diff->days);
}

function statusBadge(string $status): string {
    $map = [
        'pending'      => 'warning',
        'confirmed'    => 'info',
        'checked_in'   => 'success',
        'checked_out'  => 'secondary',
        'cancelled'    => 'danger',
        'no_show'      => 'dark',
        'available'    => 'success',
        'occupied'     => 'primary',
        'cleaning'     => 'warning',
        'maintenance'  => 'danger',
        'out_of_order' => 'dark',
        'completed'    => 'success',
        'refunded'     => 'secondary',
        'failed'       => 'danger',
        'in_progress'  => 'info',
        'done'         => 'success',
        'skipped'      => 'secondary',
    ];
    $color = $map[$status] ?? 'secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge badge-{$color}\">{$label}</span>";
}

function generateBookingRef(): string {
    return 'BK-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
}

function redirect(string $path): never {
    header('Location: ' . BASE_URL . $path);
    exit;
}

function jsonResponse(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrfToken(): string {
    Auth::start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void {
    Auth::start();
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

function flash(string $key, string $message = ''): ?string {
    Auth::start();
    if ($message !== '') {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function isDoubleBooked(int $roomId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): bool {
    $sql = "SELECT COUNT(*) as cnt FROM bookings
            WHERE room_id = ?
              AND status NOT IN ('cancelled','checked_out','no_show')
              AND check_in  < ?
              AND check_out > ?";
    $params = [$roomId, $checkOut, $checkIn];
    if ($excludeBookingId) {
        $sql .= ' AND id != ?';
        $params[] = $excludeBookingId;
    }
    $row = Database::queryOne($sql, $params);
    return (int)($row['cnt'] ?? 0) > 0;
}
