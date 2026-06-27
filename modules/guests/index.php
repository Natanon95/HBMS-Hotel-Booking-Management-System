<?php
$pageTitle = 'Guests';
require_once __DIR__ . '/../../includes/header.php';

$search  = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$where  = '1=1';
$params = [];
if ($search !== '') {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR id_number LIKE ?)";
    $s = "%{$search}%";
    $params = [$s, $s, $s, $s, $s];
}

// CSV export
if (isset($_GET['export'])) {
    $rows = Database::query("SELECT * FROM guests WHERE {$where} ORDER BY last_name, first_name", $params);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="guests_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','First Name','Last Name','Email','Phone','ID Type','ID Number','Nationality','Created']);
    foreach ($rows as $r) fputcsv($out, [$r['id'],$r['first_name'],$r['last_name'],$r['email'],$r['phone'],$r['id_type'],$r['id_number'],$r['nationality'],$r['created_at']]);
    fclose($out); exit;
}

$total  = Database::queryOne("SELECT COUNT(*) c FROM guests WHERE {$where}", $params)['c'] ?? 0;
$guests = Database::query("SELECT * FROM guests WHERE {$where} ORDER BY last_name, first_name LIMIT {$perPage} OFFSET {$offset}", $params);
$totalPages = (int)ceil($total / $perPage);
?>
<div class="page-header">
  <h1>Guests</h1>
  <a href="create.php" class="btn btn-primary">+ New Guest</a>
  <a href="?q=<?= urlencode($search) ?>&export=1" class="btn btn-ghost">Export CSV</a>
</div>

<div class="card">
  <div class="card-header">
    <form method="get" class="d-flex gap-8" style="flex:1">
      <input type="text" name="q" value="<?= e($search) ?>" class="form-control" style="max-width:300px" placeholder="Search name, email, ID…">
      <button type="submit" class="btn btn-ghost">Search</button>
      <?php if ($search): ?><a href="./" class="btn btn-ghost">Clear</a><?php endif; ?>
    </form>
    <span class="text-sm text-muted"><?= number_format($total) ?> guest<?= $total!==1?'s':'' ?></span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Nationality</th><th>Bookings</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if (empty($guests)): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:30px">No guests found</td></tr>
      <?php else: ?>
        <?php foreach ($guests as $g):
          $bc = Database::queryOne("SELECT COUNT(*) c FROM bookings WHERE guest_id=?",[$g['id']])['c'] ?? 0;
        ?>
        <tr>
          <td><a href="view.php?id=<?= $g['id'] ?>"><?= e($g['first_name'].' '.$g['last_name']) ?></a></td>
          <td><?= e($g['email'] ?? '—') ?></td>
          <td><?= e($g['phone'] ?? '—') ?></td>
          <td><?= e($g['nationality'] ?? '—') ?></td>
          <td><?= $bc ?></td>
          <td>
            <a href="view.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">View</a>
            <a href="edit.php?id=<?= $g['id'] ?>" class="btn btn-sm btn-ghost">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($totalPages > 1): ?>
  <div class="card-footer d-flex gap-8 align-center">
    <?php if ($page > 1): ?><a href="?q=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="btn btn-sm btn-ghost">← Prev</a><?php endif; ?>
    <span class="text-sm text-muted">Page <?= $page ?> of <?= $totalPages ?></span>
    <?php if ($page < $totalPages): ?><a href="?q=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="btn btn-sm btn-ghost">Next →</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
