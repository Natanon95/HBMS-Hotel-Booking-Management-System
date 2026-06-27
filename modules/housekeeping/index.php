<?php
$pageTitle = 'Housekeeping';
require_once __DIR__ . '/../../includes/header.php';

$date   = $_GET['date'] ?? date('Y-m-d');
$filter = $_GET['status'] ?? 'all';

$where  = 'h.scheduled_date=?';
$params = [$date];
if ($filter !== 'all') { $where .= ' AND h.status=?'; $params[] = $filter; }

$tasks = Database::query("
    SELECT h.*, r.room_number, rt.name AS room_type,
           u.name AS assigned_name
    FROM housekeeping h
    JOIN rooms r ON r.id=h.room_id
    JOIN room_types rt ON rt.id=r.room_type_id
    LEFT JOIN users u ON u.id=h.assigned_to
    WHERE {$where}
    ORDER BY FIELD(h.status,'in_progress','pending','done','skipped'), h.room_id", $params);

$summary = Database::queryOne("
    SELECT
        SUM(status='pending') pending,
        SUM(status='in_progress') in_progress,
        SUM(status='done') done,
        SUM(status='skipped') skipped
    FROM housekeeping WHERE scheduled_date=?", [$date]);
?>

<div class="page-header">
  <h1>Housekeeping</h1>
  <button class="btn btn-primary btn-sm" data-modal-open="addTaskModal">+ Add Task</button>
</div>

<!-- Summary badges -->
<div class="d-flex gap-12 mb-24 flex-wrap">
  <?php foreach (['pending'=>'warning','in_progress'=>'info','done'=>'success','skipped'=>'secondary'] as $s => $c): ?>
    <a href="?date=<?= e($date) ?>&status=<?= $s ?>" class="badge badge-<?= $c ?>" style="font-size:.8rem;padding:6px 14px;cursor:pointer">
      <?= ucfirst(str_replace('_',' ',$s)) ?>: <?= (int)($summary[$s] ?? 0) ?>
    </a>
  <?php endforeach; ?>
  <?php if ($filter !== 'all'): ?>
    <a href="?date=<?= e($date) ?>" class="btn btn-ghost btn-sm">Clear filter</a>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header">
    <form method="get" class="d-flex gap-8 align-center">
      <input type="hidden" name="status" value="<?= e($filter) ?>">
      <label class="form-label mb-0">Date:</label>
      <input type="date" name="date" class="form-control" value="<?= e($date) ?>" style="width:180px">
      <button type="submit" class="btn btn-ghost btn-sm">Go</button>
      <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-ghost btn-sm">Today</a>
    </form>
    <span class="text-sm text-muted"><?= count($tasks) ?> task<?= count($tasks)!==1?'s':'' ?></span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Room</th><th>Type</th><th>Task</th><th>Status</th><th>Assigned To</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if (empty($tasks)): ?>
        <tr><td colspan="6" class="text-center text-muted" style="padding:30px">No tasks for this date</td></tr>
      <?php else: ?>
        <?php foreach ($tasks as $t): ?>
          <tr>
            <td><strong><?= e($t['room_number']) ?></strong> <span class="text-sm text-muted"><?= e($t['room_type']) ?></span></td>
            <td><?= e(ucfirst(str_replace('_',' ',$t['task_type']))) ?></td>
            <td><?= $t['booking_id'] ? '<a href="'.BASE_URL.'/modules/bookings/view.php?id='.$t['booking_id'].'">Booking</a>' : '—' ?></td>
            <td><?= statusBadge($t['status']) ?></td>
            <td><?= e($t['assigned_name'] ?? 'Unassigned') ?></td>
            <td>
              <div class="d-flex gap-8">
                <?php if ($t['status'] === 'pending'): ?>
                  <form method="post" action="update.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit" class="btn btn-sm btn-info">Start</button>
                  </form>
                <?php elseif ($t['status'] === 'in_progress'): ?>
                  <form method="post" action="update.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <input type="hidden" name="status" value="done">
                    <button type="submit" class="btn btn-sm btn-success">Done</button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add task modal -->
<div class="modal-overlay hidden" id="addTaskModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Add Housekeeping Task</span>
      <button class="btn-icon" data-modal-close="addTaskModal">✕</button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/api/housekeeping.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Room</label>
          <select name="room_id" class="form-control">
            <?php
            $allRooms = Database::query("SELECT r.id, r.room_number FROM rooms r ORDER BY r.room_number");
            foreach ($allRooms as $rm): ?>
              <option value="<?= $rm['id'] ?>"><?= e($rm['room_number']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Task Type</label>
          <select name="task_type" class="form-control">
            <?php foreach (['checkout_clean','daily_clean','deep_clean','maintenance','inspection'] as $tt): ?>
              <option value="<?= $tt ?>"><?= ucfirst(str_replace('_',' ',$tt)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Scheduled Date</label>
          <input type="date" name="scheduled_date" class="form-control" value="<?= e($date) ?>">
        </div>
        <div class="form-group"><label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="addTaskModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Task</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
