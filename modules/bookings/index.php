<?php
$pageTitle = 'Bookings';
require_once __DIR__ . '/../../includes/header.php';

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (b.booking_ref LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ? OR g.email LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$statusMap = [
    'arrivals'   => "b.status='confirmed' AND b.check_in=CURDATE()",
    'departures' => "b.status='checked_in' AND b.check_out=CURDATE()",
    'inhouse'    => "b.status='checked_in'",
    'pending'    => "b.status='pending'",
    'cancelled'  => "b.status='cancelled'",
];
if (isset($statusMap[$filter])) {
    $where .= ' AND (' . $statusMap[$filter] . ')';
}

$total = Database::queryOne("
    SELECT COUNT(*) c FROM bookings b
    JOIN guests g ON g.id=b.guest_id
    WHERE {$where}", $params)['c'] ?? 0;

$bookings = Database::query("
    SELECT b.*, g.first_name, g.last_name, g.email AS guest_email,
           r.room_number, rt.name AS room_type
    FROM bookings b
    JOIN guests g    ON g.id  = b.guest_id
    JOIN rooms  r    ON r.id  = b.room_id
    JOIN room_types rt ON rt.id = r.room_type_id
    WHERE {$where}
    ORDER BY b.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}",
    array_merge($params, []));

$totalPages = (int)ceil($total / $perPage);
$tabs = [
    'all'        => 'All',
    'arrivals'   => 'Arrivals Today',
    'departures' => 'Departures Today',
    'inhouse'    => 'In-House',
    'pending'    => 'Pending',
    'cancelled'  => 'Cancelled',
];

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $allRows = Database::query("
        SELECT b.booking_ref, g.first_name, g.last_name, g.email AS guest_email,
               r.room_number, rt.name AS room_type,
               b.check_in, b.check_out, b.adults, b.children,
               b.status, b.room_rate, b.total_amount, b.source, b.created_at
        FROM bookings b
        JOIN guests g    ON g.id  = b.guest_id
        JOIN rooms  r    ON r.id  = b.room_id
        JOIN room_types rt ON rt.id = r.room_type_id
        WHERE {$where}
        ORDER BY b.created_at DESC", $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bookings_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Ref','First Name','Last Name','Email','Room','Type','Check-in','Check-out','Adults','Children','Status','Rate','Total','Source','Created']);
    foreach ($allRows as $row) fputcsv($out, array_values($row));
    fclose($out);
    exit;
}
?>

<div class="page-header">
  <h1>Bookings</h1>
  <a href="<?= BASE_URL ?>/modules/bookings/create.php" class="btn btn-primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Booking
  </a>
  <a href="?filter=<?= e($filter) ?>&q=<?= urlencode($search) ?>&export=csv" class="btn btn-ghost">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Export CSV
  </a>
</div>

<div class="tabs">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="tab <?= $filter === $key ? 'active' : '' ?>"
       href="?filter=<?= $key ?>&q=<?= urlencode($search) ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">
    <form method="get" class="d-flex gap-8" style="flex:1">
      <input type="hidden" name="filter" value="<?= e($filter) ?>">
      <input type="text" name="q" value="<?= e($search) ?>" class="form-control" style="max-width:280px" placeholder="Search ref, guest, email…">
      <button type="submit" class="btn btn-ghost">Search</button>
      <?php if ($search): ?><a href="?filter=<?= e($filter) ?>" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form>
    <span class="text-sm text-muted"><?= number_format($total) ?> booking<?= $total !== 1 ? 's' : '' ?></span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Ref</th><th>Guest</th><th>Room</th>
          <th>Check-in</th><th>Check-out</th><th>Nights</th>
          <th>Status</th><th>Total</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="9" class="text-center text-muted" style="padding:30px">No bookings found</td></tr>
      <?php else: ?>
        <?php foreach ($bookings as $b): ?>
        <tr>
          <td><a href="view.php?id=<?= $b['id'] ?>" class="fw-600"><?= e($b['booking_ref']) ?></a></td>
          <td>
            <a href="<?= BASE_URL ?>/modules/guests/view.php?id=<?= $b['guest_id'] ?>"><?= e($b['first_name'].' '.$b['last_name']) ?></a>
          </td>
          <td><?= e($b['room_number']) ?> <span class="text-sm text-muted"><?= e($b['room_type']) ?></span></td>
          <td><?= formatDate($b['check_in']) ?></td>
          <td><?= formatDate($b['check_out']) ?></td>
          <td><?= nights($b['check_in'], $b['check_out']) ?></td>
          <td><?= statusBadge($b['status']) ?></td>
          <td><?= formatMoney((float)$b['total_amount']) ?></td>
          <td>
            <div class="d-flex gap-8">
              <a href="view.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-ghost">View</a>
              <?php if ($b['status'] === 'confirmed'): ?>
                <a href="checkin.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-success">Check In</a>
              <?php elseif ($b['status'] === 'checked_in'): ?>
                <a href="checkout.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-primary">Check Out</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex gap-8 align-center">
    <?php if ($page > 1): ?>
      <a href="?filter=<?= e($filter) ?>&q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-sm btn-ghost">← Prev</a>
    <?php endif; ?>
    <span class="text-sm text-muted">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?>
      <a href="?filter=<?= e($filter) ?>&q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-sm btn-ghost">Next →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
