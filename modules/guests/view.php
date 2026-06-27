<?php
$id = (int)($_GET['id'] ?? 0);
$guest = Database::queryOne("SELECT * FROM guests WHERE id=?", [$id]);
if (!$guest) { http_response_code(404); die('Guest not found'); }
$pageTitle = $guest['first_name'] . ' ' . $guest['last_name'];
require_once __DIR__ . '/../../includes/header.php';

$bookings = Database::query("
    SELECT b.*, r.room_number, rt.name AS room_type
    FROM bookings b
    JOIN rooms r ON r.id=b.room_id JOIN room_types rt ON rt.id=r.room_type_id
    WHERE b.guest_id=? ORDER BY b.check_in DESC", [$id]);
$totalSpend = Database::queryOne("
    SELECT COALESCE(SUM(p.amount),0) total FROM payments p
    JOIN bookings b ON b.id=p.booking_id WHERE b.guest_id=? AND p.status='completed'", [$id])['total'] ?? 0;
?>
<div class="page-header">
  <a href="./" class="btn btn-ghost btn-sm">← Guests</a>
  <h1><?= e($guest['first_name'].' '.$guest['last_name']) ?></h1>
  <a href="edit.php?id=<?= $id ?>" class="btn btn-ghost btn-sm ml-auto">Edit</a>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Guest Details</span></div>
    <div class="card-body">
      <table class="w-100" style="border:none">
        <?php $rows = [
          'Email'=>$guest['email']??'—','Phone'=>$guest['phone']??'—',
          'ID Type'=>ucfirst(str_replace('_',' ',$guest['id_type']??'')),
          'ID Number'=>$guest['id_number']??'—','Nationality'=>$guest['nationality']??'—',
          'Address'=>$guest['address']??'—','Notes'=>$guest['notes']??'—',
          'Member Since'=>formatDate($guest['created_at']),
        ];
        foreach ($rows as $label => $val): ?>
          <tr><td class="text-muted text-sm" style="width:120px;padding:5px 0"><?= $label ?></td><td><?= e($val) ?></td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Stats</span></div>
    <div class="card-body">
      <div class="stat-card" style="border:none;padding:0;box-shadow:none;margin-bottom:12px">
        <div class="stat-icon blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></div>
        <div><div class="stat-label">Total Stays</div><div class="stat-value"><?= count($bookings) ?></div></div>
      </div>
      <div class="stat-card" style="border:none;padding:0;box-shadow:none">
        <div class="stat-icon green"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg></div>
        <div><div class="stat-label">Total Spend</div><div class="stat-value"><?= formatMoney((float)$totalSpend) ?></div></div>
      </div>
    </div>
  </div>
</div>

<div class="card mt-16">
  <div class="card-header">
    <span class="card-title">Booking History</span>
    <a href="<?= BASE_URL ?>/modules/bookings/create.php" class="btn btn-sm btn-primary">+ New Booking</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ref</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Total</th></tr></thead>
      <tbody>
      <?php if (empty($bookings)): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:20px">No bookings yet</td></tr>
      <?php else: ?>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/modules/bookings/view.php?id=<?= $b['id'] ?>"><?= e($b['booking_ref']) ?></a></td>
            <td><?= e($b['room_number']) ?> <span class="text-sm text-muted"><?= e($b['room_type']) ?></span></td>
            <td><?= formatDate($b['check_in']) ?></td>
            <td><?= formatDate($b['check_out']) ?></td>
            <td><?= statusBadge($b['status']) ?></td>
            <td><?= formatMoney((float)$b['total_amount']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
