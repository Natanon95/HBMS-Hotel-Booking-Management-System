<?php
$pageTitle = 'Rooms';
require_once __DIR__ . '/../../includes/header.php';

$rooms = Database::query("
    SELECT r.*, rt.name AS type_name, rt.base_price
    FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id
    ORDER BY r.floor, r.room_number");
$types = Database::query("SELECT * FROM room_types ORDER BY base_price");
?>
<div class="page-header">
  <h1>Rooms</h1>
  <?php if (Auth::isAdmin()): ?>
    <button class="btn btn-primary btn-sm" data-modal-open="addRoomModal">+ Add Room</button>
  <?php endif; ?>
</div>

<!-- Summary -->
<?php
$summary = ['available'=>0,'occupied'=>0,'cleaning'=>0,'maintenance'=>0,'out_of_order'=>0];
foreach ($rooms as $r) { if (isset($summary[$r['status']])) $summary[$r['status']]++; }
?>
<div class="stat-grid mb-24">
  <?php $cols = ['available'=>'green','occupied'=>'blue','cleaning'=>'yellow','maintenance'=>'red','out_of_order'=>'dark']; ?>
  <?php foreach ($summary as $st => $cnt): ?>
  <div class="stat-card">
    <div class="stat-icon <?= $cols[$st] ?? 'blue' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
      </svg>
    </div>
    <div>
      <div class="stat-label"><?= ucfirst(str_replace('_',' ',$st)) ?></div>
      <div class="stat-value"><?= $cnt ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Room grid -->
<div class="card mb-24">
  <div class="card-header"><span class="card-title">All Rooms</span></div>
  <div class="card-body">
    <div class="room-grid">
      <?php foreach ($rooms as $rm): ?>
      <a href="view.php?id=<?= $rm['id'] ?>" style="text-decoration:none;color:inherit">
        <div class="room-cell <?= e($rm['status']) ?>">
          <div class="room-number"><?= e($rm['room_number']) ?></div>
          <div class="room-type"><?= e($rm['type_name']) ?></div>
          <div class="room-status"><?= formatMoney((float)$rm['base_price']) ?>/night</div>
          <div class="room-status text-<?= match($rm['status']){
            'available'=>'success','occupied'=>'primary','cleaning'=>'warning',
            'maintenance','out_of_order'=>'danger',default=>'muted'
          } ?> mt-4"><?= ucfirst(str_replace('_',' ',$rm['status'])) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Room table -->
<div class="card">
  <div class="card-header"><span class="card-title">Room List</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Room</th><th>Type</th><th>Floor</th><th>Rate/Night</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($rooms as $rm): ?>
      <tr>
        <td><strong><?= e($rm['room_number']) ?></strong></td>
        <td><?= e($rm['type_name']) ?></td>
        <td><?= $rm['floor'] ?></td>
        <td><?= formatMoney((float)$rm['base_price']) ?></td>
        <td><?= statusBadge($rm['status']) ?></td>
        <td><a href="view.php?id=<?= $rm['id'] ?>" class="btn btn-sm btn-ghost">View</a></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Room Modal (admin only) -->
<?php if (Auth::isAdmin()): ?>
<div class="modal-overlay hidden" id="addRoomModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Add Room</span>
      <button class="btn-icon" data-modal-close="addRoomModal">✕</button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/api/rooms.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Room Number</label>
            <input name="room_number" class="form-control" required placeholder="e.g. 501">
          </div>
          <div class="form-group"><label class="form-label">Floor</label>
            <input name="floor" type="number" class="form-control" value="1" min="1">
          </div>
        </div>
        <div class="form-group"><label class="form-label">Room Type</label>
          <select name="room_type_id" class="form-control">
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['id'] ?>"><?= e($t['name']) ?> — <?= formatMoney((float)$t['base_price']) ?>/night</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="addRoomModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Room</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
