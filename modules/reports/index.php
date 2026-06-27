<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../includes/header.php';
Auth::requireRole('admin', 'receptionist');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

// Revenue by day
$revenueByDay = Database::query("
    SELECT DATE(paid_at) d, SUM(amount) rev, COUNT(*) cnt
    FROM payments WHERE status='completed' AND DATE(paid_at) BETWEEN ? AND ?
    GROUP BY DATE(paid_at) ORDER BY d", [$from, $to]);

// Bookings by status
$byStatus = Database::query("
    SELECT status, COUNT(*) cnt, COALESCE(SUM(total_amount),0) total
    FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status", [$from, $to]);

// Top room types
$topTypes = Database::query("
    SELECT rt.name, COUNT(*) bookings, SUM(b.total_amount) revenue
    FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id
    WHERE DATE(b.created_at) BETWEEN ? AND ? AND b.status != 'cancelled'
    GROUP BY rt.name ORDER BY bookings DESC", [$from, $to]);

// Source breakdown
$sources = Database::query("
    SELECT source, COUNT(*) cnt
    FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY source ORDER BY cnt DESC", [$from, $to]);

$totalRev  = array_sum(array_column($revenueByDay, 'rev'));
$totalBk   = array_sum(array_column($byStatus, 'cnt'));

// CSV export
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_'.$from.'_to_'.$to.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Date','Revenue','Transactions']);
    foreach ($revenueByDay as $r) fputcsv($out,[$r['d'],$r['rev'],$r['cnt']]);
    fclose($out); exit;
}

$chartLabels = array_column($revenueByDay, 'd');
$chartValues = array_column($revenueByDay, 'rev');
?>

<div class="page-header">
  <h1>Reports</h1>
  <a href="?from=<?= e($from) ?>&to=<?= e($to) ?>&export=1" class="btn btn-ghost">Export CSV</a>
</div>

<div class="card mb-24">
  <div class="card-body">
    <form method="get" class="d-flex gap-12 align-center flex-wrap">
      <div class="form-group mb-0">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="<?= e($from) ?>">
      </div>
      <div class="form-group mb-0">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="<?= e($to) ?>">
      </div>
      <div style="margin-top:20px"><button type="submit" class="btn btn-primary">Apply</button></div>
      <div style="margin-top:20px">
        <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">This Month</a>
        <a href="?from=<?= date('Y-m-01',strtotime('last month')) ?>&to=<?= date('Y-m-t',strtotime('last month')) ?>" class="btn btn-ghost btn-sm">Last Month</a>
        <a href="?from=<?= date('Y-01-01') ?>&to=<?= date('Y-12-31') ?>" class="btn btn-ghost btn-sm">This Year</a>
      </div>
    </form>
  </div>
</div>

<div class="stat-grid mb-24">
  <div class="stat-card">
    <div class="stat-icon green"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg></div>
    <div><div class="stat-label">Total Revenue</div><div class="stat-value"><?= formatMoney($totalRev) ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
    <div><div class="stat-label">Total Bookings</div><div class="stat-value"><?= $totalBk ?></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
    <div><div class="stat-label">Avg Revenue/Booking</div><div class="stat-value"><?= $totalBk > 0 ? formatMoney($totalRev / $totalBk) : '—' ?></div></div>
  </div>
</div>

<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><span class="card-title">Revenue by Day</span></div>
    <div class="card-body"><div class="chart-container"><canvas id="revChart"></canvas></div></div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Bookings by Status</span></div>
    <div class="card-body">
      <?php foreach ($byStatus as $row): ?>
        <div class="d-flex align-center mb-16">
          <?= statusBadge($row['status']) ?>
          <span class="ml-auto fw-600"><?= $row['cnt'] ?></span>
          <span class="text-muted text-sm" style="width:120px;text-align:right"><?= formatMoney((float)$row['total']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header"><span class="card-title">Revenue by Room Type</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Room Type</th><th>Bookings</th><th>Revenue</th></tr></thead>
        <tbody>
        <?php foreach ($topTypes as $t): ?>
          <tr>
            <td><?= e($t['name']) ?></td>
            <td><?= $t['bookings'] ?></td>
            <td><?= formatMoney((float)$t['revenue']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Booking Source</span></div>
    <div class="card-body">
      <?php foreach ($sources as $s): ?>
        <div class="d-flex align-center mb-16">
          <span><?= ucfirst(str_replace('_',' ',$s['source'])) ?></span>
          <span class="ml-auto fw-600"><?= $s['cnt'] ?></span>
        </div>
        <div style="height:6px;background:var(--border);border-radius:3px;margin-bottom:12px">
          <div style="height:100%;border-radius:3px;background:var(--primary);width:<?= min(100, round($s['cnt'] / max(1,$totalBk) * 100)) ?>%"></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php
$extraJs = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script><script>
new Chart(document.getElementById("revChart"),{
  type:"line",
  data:{
    labels:'.json_encode($chartLabels).',
    datasets:[{label:"Revenue",data:'.json_encode($chartValues).',borderColor:"#185FA5",backgroundColor:"rgba(24,95,165,.1)",fill:true,tension:.3,borderWidth:2}]
  },
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>"฿"+v.toLocaleString()}},x:{grid:{display:false}}}}
});
</script>';
include __DIR__ . '/../../includes/footer.php'; ?>
