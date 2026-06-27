<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/helpers.php';
Auth::require();

$id = (int)($_GET['id'] ?? 0);
$booking = Database::queryOne("SELECT * FROM bookings WHERE id=? AND status NOT IN ('checked_out','cancelled','no_show')", [$id]);
if (!$booking) { redirect('/modules/bookings/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $reason = trim($_POST['reason'] ?? '');
    Database::beginTransaction();
    try {
        Database::execute("UPDATE bookings SET status='cancelled', cancelled_at=NOW(), cancel_reason=? WHERE id=?", [$reason, $id]);
        if ($booking['status'] === 'checked_in') {
            Database::execute("UPDATE rooms SET status='cleaning' WHERE id=?", [$booking['room_id']]);
        } elseif ($booking['status'] === 'confirmed') {
            Database::execute("UPDATE rooms SET status='available' WHERE id=?", [$booking['room_id']]);
        }
        Database::commit();
        flash('success', "Booking {$booking['booking_ref']} cancelled.");
        redirect("/modules/bookings/view.php?id={$id}");
    } catch (Exception $e) {
        Database::rollback();
        flash('danger', 'Cancellation failed: ' . $e->getMessage());
        redirect("/modules/bookings/view.php?id={$id}");
    }
}

$pageTitle = 'Cancel Booking';
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header">
  <a href="view.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">← Back</a>
  <h1>Cancel Booking <?= e($booking['booking_ref']) ?></h1>
</div>
<div class="card" style="max-width:500px">
  <div class="card-body">
    <div class="alert alert-warning">This will cancel the booking and cannot be easily reversed.</div>
    <form method="post">
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label">Cancellation Reason</label>
        <textarea name="reason" class="form-control" rows="3" placeholder="Optional reason…"></textarea>
      </div>
      <div class="d-flex gap-12">
        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
        <a href="view.php?id=<?= $id ?>" class="btn btn-ghost">Go Back</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
