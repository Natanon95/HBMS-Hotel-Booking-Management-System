<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';

// --- Stats ---
$today = date('Y-m-d');

$occupancy = Database::queryOne("
    SELECT
        COUNT(*) AS total,
        SUM(status='occupied') AS occupied,
        SUM(status='available') AS available,
        SUM(status='cleaning') AS cleaning,
        SUM(status='maintenance') AS maintenance
    FROM rooms
");

$todayArrivals = Database::queryOne(
    "SELECT COUNT(*) c FROM bookings WHERE status='confirmed' AND check_in=?", [$today]
)['c'] ?? 0;

$todayDepartures = Database::queryOne(
    "SELECT COUNT(*) c FROM bookings WHERE status='checked_in' AND check_out=?", [$today]
)['c'] ?? 0;

$monthRevenue = Database::queryOne("
    SELECT COALESCE(SUM(amount),0) total
    FROM payments
    WHERE status='completed' AND MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())
")['total'] ?? 0;

$pendingPayments = Database::queryOne("
    SELECT COUNT(*) c FROM bookings
    WHERE status IN ('confirmed','checked_in')
      AND id NOT IN (SELECT booking_id FROM payments WHERE status='completed')
")['c'] ?? 0;

// --- Today's arrivals list ---
$arrivals = Database::query("
    SELECT b.*, g.first_name, g.last_name, r.room_number, rt.name AS room_type
    FROM bookings b
    JOIN guests g ON g.id = b.guest_id
    JOIN rooms  r ON r.id = b.room_id
    JOIN room_types rt ON rt.id = r.room_type_id
    WHERE b.status='confirmed' AND b.check_in=?
    ORDER BY b.booking_ref
", [$today]);

// --- Today's departures list ---
$departures = Database::query("
    SELECT b.*, g.first_name, g.last_name, r.room_number
    FROM bookings b
    JOIN guests g ON g.id = b.guest_id
    JOIN rooms  r ON r.id = b.room_id
    WHERE b.status='checked_in' AND b.check_out=?
    ORDER BY b.booking_ref
", [$today]);

// --- Revenue last 7 days ---
$revenueData = Database::query("
    SELECT DATE(paid_at) AS d, COALESCE(SUM(amount),0) AS rev
    FROM payments
    WHERE status='completed' AND paid_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(paid_at)
    ORDER BY d
");
$revLabels = [];
$revValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $revLabels[] = date('d M', strtotime($d));
    $found = 0;
    foreach ($revenueData as $row) {
        if ($row['d'] === $d) { $found = (float)$row['rev']; break; }
    }
    $revValues[] = $found;
}

// --- Booking source breakdown ---
$sources = Database::query("
    SELECT source, COUNT(*) c
    FROM bookings
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY
    GROUP BY source
");

// --- Housekeeping tasks today ---
$hkTasks = Database::query("
    SELECT h.*, r.room_number
    FROM housekeeping h
    JOIN rooms r ON r.id = h.room_id
    WHERE h.scheduled_date = ? AND h.status != 'done'
    ORDER BY h.status
", [$today]);

$occRate = $occupancy['total'] > 0 ? round($occupancy['occupied'] / $occupancy['total'] * 100) : 0;
?>

<!-- Stat cards -->
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-icon blue">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
        <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
      </svg>
    </div>
    <div>
      <div class="stat-label">Occupancy</div>
      <div class="stat-value"><?= $occRate ?>%</div>
      <div class="stat-sub"><?= $occupancy['occupied'] ?>/<?= $occupancy['total'] ?> rooms</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>
      </svg>
    </div>
    <div>
      <div class="stat-label">Revenue (This Month)</div>
      <div class="stat-value"><?= formatMoney((float)$monthRevenue) ?></div>
      <div class="stat-sub">Completed payments</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon yellow">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
    </div>
    <div>
      <div class="stat-label">Today's Arrivals</div>
      <div class="stat-value"><?= $todayArrivals ?></div>
      <div class="stat-sub">Confirmed check-ins</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon cyan">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </div>
    <div>
      <div class="stat-label">Today's Departures</div>
      <div class="stat-value"><?= $todayDepartures ?></div>
      <div class="stat-sub">Expected check-outs</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon red">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
      </svg>
    </div>
    <div>
      <div class="stat-label">Awaiting Payment</div>
      <div class="stat-value"><?= $pendingPayments ?></div>
      <div class="stat-sub">Bookings unpaid</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
    </div>
    <div>
      <div class="stat-label">Room Status</div>
      <div class="stat-value"><?= $occupancy['available'] ?></div>
      <div class="stat-sub"><?= $occupancy['cleaning'] ?> cleaning · <?= $occupancy['maintenance'] ?> maint.</div>
    </div>
  </div>
</div>

<!-- Revenue chart + Today's Arrivals -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
      </svg>
      <span class="card-title">Revenue — Last 7 Days</span>
    </div>
    <div class="card-body">
      <div class="chart-container">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
      </svg>
      <span class="card-title">Today's Arrivals (<?= date('d M') ?>)</span>
      <?php if ($todayArrivals > 0): ?>
        <a href="<?= BASE_URL ?>/modules/bookings/?filter=arrivals" class="btn btn-sm btn-outline">View All</a>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($arrivals)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted)">No arrivals today</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Nights</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($arrivals as $a): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/modules/bookings/view.php?id=<?= $a['id'] ?>"><?= e($a['booking_ref']) ?></a></td>
            <td><?= e($a['first_name'].' '.$a['last_name']) ?></td>
            <td><?= e($a['room_number']) ?> <span class="text-muted text-sm"><?= e($a['room_type']) ?></span></td>
            <td><?= nights($a['check_in'], $a['check_out']) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/modules/bookings/checkin.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-success">Check In</a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Departures + Housekeeping -->
<div class="grid-2 mb-24">
  <div class="card">
    <div class="card-header">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      <span class="card-title">Today's Departures</span>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($departures)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted)">No departures today</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($departures as $d): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/modules/bookings/view.php?id=<?= $d['id'] ?>"><?= e($d['booking_ref']) ?></a></td>
            <td><?= e($d['first_name'].' '.$d['last_name']) ?></td>
            <td><?= e($d['room_number']) ?></td>
            <td>
              <a href="<?= BASE_URL ?>/modules/bookings/checkout.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-primary">Check Out</a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 3h18v18H3z"/><path d="M3 9h18M9 21V9"/>
      </svg>
      <span class="card-title">Housekeeping Today</span>
      <a href="<?= BASE_URL ?>/modules/housekeeping/" class="btn btn-sm btn-ghost">Full List</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($hkTasks)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted)">All rooms clean ✓</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Room</th><th>Task</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($hkTasks as $t): ?>
          <tr>
            <td><strong><?= e($t['room_number']) ?></strong></td>
            <td><?= e(ucwords(str_replace('_',' ',$t['task_type']))) ?></td>
            <td><?= statusBadge($t['status']) ?></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Room occupancy quick view -->
<div class="card mb-24">
  <div class="card-header">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
    </svg>
    <span class="card-title">Room Overview</span>
    <a href="<?= BASE_URL ?>/modules/rooms/" class="btn btn-sm btn-ghost">Manage Rooms</a>
  </div>
  <div class="card-body">
    <?php
    $rooms = Database::query("
        SELECT r.*, rt.name AS type_name
        FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id
        ORDER BY r.floor, r.room_number
    ");
    ?>
    <div class="room-grid">
      <?php foreach ($rooms as $rm): ?>
      <a href="<?= BASE_URL ?>/modules/rooms/view.php?id=<?= $rm['id'] ?>" style="text-decoration:none;color:inherit">
        <div class="room-cell <?= e($rm['status']) ?>">
          <div class="room-number"><?= e($rm['room_number']) ?></div>
          <div class="room-type"><?= e($rm['type_name']) ?></div>
          <div class="room-status text-<?= match($rm['status']){
            'available'=>'success','occupied'=>'primary','cleaning'=>'warning',
            'maintenance','out_of_order'=>'danger', default=>'muted'
          } ?>"><?= ucfirst($rm['status']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <div class="mt-16 d-flex gap-12 text-sm text-muted">
      <span style="display:inline-flex;align-items:center;gap:5px">
        <span style="width:10px;height:10px;border-radius:2px;background:var(--success);display:inline-block"></span>Available
      </span>
      <span style="display:inline-flex;align-items:center;gap:5px">
        <span style="width:10px;height:10px;border-radius:2px;background:var(--primary);display:inline-block"></span>Occupied
      </span>
      <span style="display:inline-flex;align-items:center;gap:5px">
        <span style="width:10px;height:10px;border-radius:2px;background:var(--warning);display:inline-block"></span>Cleaning
      </span>
      <span style="display:inline-flex;align-items:center;gap:5px">
        <span style="width:10px;height:10px;border-radius:2px;background:var(--danger);display:inline-block"></span>Maintenance
      </span>
    </div>
  </div>
</div>

<?php
$extraJs = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode($revLabels) ?>;
const values = <?= json_encode($revValues) ?>;
new Chart(document.getElementById('revenueChart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Revenue (฿)',
      data: values,
      backgroundColor: 'rgba(24,95,165,.75)',
      borderColor: '#185FA5',
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: v => '฿' + v.toLocaleString()
        },
        grid: { color: 'rgba(0,0,0,.06)' }
      },
      x: { grid: { display: false } }
    }
  }
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
