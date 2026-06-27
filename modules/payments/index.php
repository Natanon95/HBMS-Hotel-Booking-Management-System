<?php
$pageTitle = 'Payments';
require_once __DIR__ . '/../../includes/header.php';

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;
$search  = trim($_GET['q'] ?? '');
$month   = $_GET['month'] ?? date('Y-m');

$where  = "YEAR(p.paid_at)=? AND MONTH(p.paid_at)=?";
$params = [substr($month,0,4), substr($month,5,2)];

if ($search !== '') {
    $where .= " AND (b.booking_ref LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s,$s,$s]);
}

// Export
if (isset($_GET['export'])) {
    $rows = Database::query("
        SELECT p.id, b.booking_ref, g.first_name, g.last_name, p.amount, p.method, p.status, p.reference, p.paid_at
        FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN guests g ON g.id=b.guest_id
        WHERE {$where} ORDER BY p.paid_at DESC", $params);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payments_'.$month.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['ID','Booking','First','Last','Amount','Method','Status','Reference','Paid At']);
    foreach ($rows as $r) fputcsv($out, array_values($r));
    fclose($out); exit;
}

$total = Database::queryOne("
    SELECT COUNT(*) c FROM payments p
    JOIN bookings b ON b.id=p.booking_id JOIN guests g ON g.id=b.guest_id
    WHERE {$where}", $params)['c'] ?? 0;

$payments = Database::query("
    SELECT p.*, b.booking_ref, g.first_name, g.last_name
    FROM payments p JOIN bookings b ON b.id=p.booking_id JOIN guests g ON g.id=b.guest_id
    WHERE {$where} ORDER BY p.paid_at DESC LIMIT {$perPage} OFFSET {$offset}",
    array_merge($params, []));

$monthTotal = Database::queryOne("
    SELECT COALESCE(SUM(p.amount),0) t FROM payments p
    JOIN bookings b ON b.id=p.booking_id JOIN guests g ON g.id=b.guest_id
    WHERE {$where} AND p.status='completed'", $params)['t'] ?? 0;

$totalPages = (int)ceil($total / $perPage);
?>
<div class="page-header">
  <h1>Payments</h1>
  <a href="?month=<?= e($month) ?>&q=<?= urlencode($search) ?>&export=1" class="btn btn-ghost">Export CSV</a>
</div>

<div class="stat-grid mb-24">
  <div class="stat-card">
    <div class="stat-icon green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg></div>
    <div><div class="stat-label">Revenue <?= date('M Y', strtotime($month.'-01')) ?></div><div class="stat-value"><?= formatMoney((float)$monthTotal) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
    <div><div class="stat-label">Transactions</div><div class="stat-value"><?= $total ?></div></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <form method="get" class="d-flex gap-8" style="flex:1;flex-wrap:wrap">
      <input type="month" name="month" value="<?= e($month) ?>" class="form-control" style="width:160px">
      <input type="text" name="q" value="<?= e($search) ?>" class="form-control" style="max-width:240px" placeholder="Search booking, guest…">
      <button type="submit" class="btn btn-ghost">Filter</button>
    </form>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Booking</th><th>Guest</th><th>Amount</th><th>Method</th><th>Status</th><th>Reference</th></tr></thead>
      <tbody>
      <?php if (empty($payments)): ?>
        <tr><td colspan="7" class="text-center text-muted" style="padding:30px">No payments found</td></tr>
      <?php else: ?>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= formatDate($p['paid_at']??'') ?></td>
            <td><a href="<?= BASE_URL ?>/modules/bookings/view.php?id=<?= $p['booking_id'] ?>"><?= e($p['booking_ref']) ?></a></td>
            <td><?= e($p['first_name'].' '.$p['last_name']) ?></td>
            <td class="fw-600"><?= formatMoney((float)$p['amount']) ?></td>
            <td><?= ucfirst(str_replace('_',' ',$p['method'])) ?></td>
            <td><?= statusBadge($p['status']) ?></td>
            <td class="text-sm text-muted"><?= e($p['reference']??'—') ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex gap-8 align-center">
    <?php if ($page > 1): ?><a href="?month=<?= e($month) ?>&q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-sm btn-ghost">← Prev</a><?php endif; ?>
    <span class="text-sm text-muted">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a href="?month=<?= e($month) ?>&q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-sm btn-ghost">Next →</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
