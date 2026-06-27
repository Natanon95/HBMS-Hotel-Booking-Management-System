<?php
$id = (int)($_GET['id'] ?? 0);
$room = Database::queryOne("
    SELECT r.*, rt.name AS type_name, rt.base_price, rt.max_occupancy, rt.description, rt.amenities
    FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id WHERE r.id=?", [$id]);
if (!$room) { http_response_code(404); die('Room not found'); }
$pageTitle = 'Room ' . $room['room_number'];
require_once __DIR__ . '/../../includes/header.php';

$currentBooking = Database::queryOne("
    SELECT b.*, g.first_name, g.last_name
    FROM bookings b JOIN guests g ON g.id=b.guest_id
    WHERE b.room_id=? AND b.status IN ('checked_in','confirmed')
    ORDER BY b.check_in LIMIT 1", [$id]);

$history = Database::query("
    SELECT b.*, g.first_name, g.last_name
    FROM bookings b JOIN guests g ON g.id=b.guest_id
    WHERE b.room_id=?
    ORDER BY b.check_in DESC LIMIT 10", [$id]);
?>
<div class="page-header">
  <a href="./" class="btn btn-ghost btn-sm">← Rooms</a>
  <h1>Room <?= e($room['room_number']) ?></h1>
  <?= statusBadge($room['status']) ?>
  <?php if (Auth::isAdmin()): ?>
    <button class="btn btn-sm btn-ghost ml-auto" data-modal-open="editStatusModal">Change Status</button>
  <?php endif; ?>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header"><span class="card-title">Room Info</span></div>
    <div class="card-body">
      <table class="w-100" style="border:none">
        <tr><td class="text-muted text-sm" style="width:130px;padding:5px 0">Type</td><td><?= e($room['type_name']) ?></td></tr>
        <tr><td class="text-muted text-sm" style="padding:5px 0">Floor</td><td><?= $room['floor'] ?></td></tr>
        <tr><td class="text-muted text-sm" style="padding:5px 0">Rate/Night</td><td><?= formatMoney((float)$room['base_price']) ?></td></tr>
        <tr><td class="text-muted text-sm" style="padding:5px 0">Max Occupancy</td><td><?= $room['max_occupancy'] ?> guests</td></tr>
        <?php if ($room['description']): ?>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Description</td><td><?= e($room['description']) ?></td></tr>
        <?php endif; ?>
        <?php if ($room['notes']): ?>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Notes</td><td><?= e($room['notes']) ?></td></tr>
        <?php endif; ?>
      </table>
      <?php
      $amenities = json_decode($room['amenities'] ?? '[]', true);
      if (!empty($amenities)): ?>
        <div class="mt-16">
          <div class="text-sm text-muted mb-8">Amenities</div>
          <div class="d-flex gap-8" style="flex-wrap:wrap">
            <?php foreach ($amenities as $a): ?>
              <span class="badge badge-secondary"><?= e($a) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Current Status</span></div>
    <div class="card-body">
      <?php if ($currentBooking): ?>
        <div class="fw-600"><?= e($currentBooking['first_name'].' '.$currentBooking['last_name']) ?></div>
        <div class="text-sm text-muted mt-8">
          <?= statusBadge($currentBooking['status']) ?>
          <?= formatDate($currentBooking['check_in']) ?> → <?= formatDate($currentBooking['check_out']) ?>
        </div>
        <div class="mt-16">
          <a href="<?= BASE_URL ?>/modules/bookings/view.php?id=<?= $currentBooking['id'] ?>" class="btn btn-sm btn-outline">
            View Booking <?= e($currentBooking['booking_ref']) ?>
          </a>
        </div>
      <?php elseif ($room['status'] === 'available'): ?>
        <div class="text-success">Room is available for booking</div>
        <div class="mt-16">
          <a href="<?= BASE_URL ?>/modules/bookings/create.php" class="btn btn-sm btn-primary">New Booking</a>
        </div>
      <?php else: ?>
        <div class="text-muted">No active booking</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card mt-16">
  <div class="card-header"><span class="card-title">Booking History</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Ref</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($history)): ?>
        <tr><td colspan="5" class="text-center text-muted" style="padding:20px">No booking history</td></tr>
      <?php else: ?>
        <?php foreach ($history as $h): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/modules/bookings/view.php?id=<?= $h['id'] ?>"><?= e($h['booking_ref']) ?></a></td>
            <td><?= e($h['first_name'].' '.$h['last_name']) ?></td>
            <td><?= formatDate($h['check_in']) ?></td>
            <td><?= formatDate($h['check_out']) ?></td>
            <td><?= statusBadge($h['status']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (Auth::isAdmin()): ?>
<div class="modal-overlay hidden" id="editStatusModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Change Room Status</span>
      <button class="btn-icon" data-modal-close="editStatusModal">✕</button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/api/rooms.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="status">
      <input type="hidden" name="room_id" value="<?= $id ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">New Status</label>
          <select name="status" class="form-control">
            <?php foreach (['available','cleaning','maintenance','out_of_order'] as $s): ?>
              <option value="<?= $s ?>" <?= $room['status']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Optional note"><?= e($room['notes']) ?></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="editStatusModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Status</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
