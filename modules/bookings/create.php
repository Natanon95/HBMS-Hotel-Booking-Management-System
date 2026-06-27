<?php
$pageTitle = 'New Booking';
require_once __DIR__ . '/../../includes/header.php';

$rooms = Database::query("
    SELECT r.*, rt.name AS type_name, rt.base_price, rt.max_occupancy
    FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id
    WHERE r.status='available'
    ORDER BY r.room_number");
$guests = Database::query("SELECT id, first_name, last_name, email FROM guests ORDER BY last_name, first_name");

$errors = [];
$values = [
    'guest_id'         => '',
    'room_id'          => '',
    'check_in'         => date('Y-m-d'),
    'check_out'        => date('Y-m-d', strtotime('+1 day')),
    'adults'           => 1,
    'children'         => 0,
    'source'           => 'walk_in',
    'special_requests' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $values = array_merge($values, array_intersect_key($_POST, $values));
    $values['adults']   = max(1, (int)$values['adults']);
    $values['children'] = max(0, (int)$values['children']);

    // Validate
    if (empty($values['guest_id']))  $errors[] = 'Guest is required.';
    if (empty($values['room_id']))   $errors[] = 'Room is required.';
    if ($values['check_out'] <= $values['check_in']) $errors[] = 'Check-out must be after check-in.';

    if (empty($errors)) {
        if (isDoubleBooked((int)$values['room_id'], $values['check_in'], $values['check_out'])) {
            $errors[] = 'This room is already booked for the selected dates.';
        }
    }

    if (empty($errors)) {
        $room = Database::queryOne("SELECT r.*, rt.base_price FROM rooms r JOIN room_types rt ON rt.id=r.room_type_id WHERE r.id=?", [$values['room_id']]);
        $n    = nights($values['check_in'], $values['check_out']);
        $rate = (float)$room['base_price'];
        $total = $rate * $n;
        $ref   = generateBookingRef();

        Database::execute("
            INSERT INTO bookings (booking_ref,guest_id,room_id,check_in,check_out,adults,children,status,room_rate,total_amount,source,special_requests,created_by)
            VALUES (?,?,?,?,?,?,?,'confirmed',?,?,?,?,?)",
            [$ref, $values['guest_id'], $values['room_id'],
             $values['check_in'], $values['check_out'],
             $values['adults'], $values['children'],
             $rate, $total, $values['source'],
             $values['special_requests'], Auth::id()]);
        $id = Database::lastInsertId();
        flash('success', "Booking {$ref} created successfully.");
        redirect("/modules/bookings/view.php?id={$id}");
    }
}
?>

<div class="page-header">
  <a href="./" class="btn btn-ghost btn-sm">← Back</a>
  <h1>New Booking</h1>
</div>

<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<form method="post">
  <?= csrfField() ?>
  <div class="grid-2">
    <!-- Left column -->
    <div>
      <div class="card mb-16">
        <div class="card-header"><span class="card-title">Guest Details</span></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Select Existing Guest</label>
            <select name="guest_id" class="form-control">
              <option value="">— choose guest —</option>
              <?php foreach ($guests as $g): ?>
                <option value="<?= $g['id'] ?>" <?= $values['guest_id'] == $g['id'] ? 'selected' : '' ?>>
                  <?= e($g['first_name'].' '.$g['last_name']) ?> (<?= e($g['email'] ?? 'no email') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="text-sm text-muted">
            Guest not found? <a href="<?= BASE_URL ?>/modules/guests/create.php?return=booking">Add new guest</a>
          </div>
        </div>
      </div>

      <div class="card mb-16">
        <div class="card-header"><span class="card-title">Booking Details</span></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Check-in Date</label>
              <input id="check_in" name="check_in" type="date" class="form-control" value="<?= e($values['check_in']) ?>" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Check-out Date</label>
              <input id="check_out" name="check_out" type="date" class="form-control" value="<?= e($values['check_out']) ?>" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Adults</label>
              <input name="adults" type="number" class="form-control" value="<?= (int)$values['adults'] ?>" min="1" max="10">
            </div>
            <div class="form-group">
              <label class="form-label">Children</label>
              <input name="children" type="number" class="form-control" value="<?= (int)$values['children'] ?>" min="0" max="10">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Source</label>
            <select name="source" class="form-control">
              <?php foreach (['walk_in','phone','online','agent'] as $s): ?>
                <option value="<?= $s ?>" <?= $values['source']===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Special Requests</label>
            <textarea name="special_requests" class="form-control" rows="3"><?= e($values['special_requests']) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div>
      <div class="card mb-16">
        <div class="card-header"><span class="card-title">Select Room</span></div>
        <div class="card-body" style="padding:0">
          <div class="table-wrap">
            <table>
              <thead><tr><th></th><th>Room</th><th>Type</th><th>Floor</th><th>Rate/Night</th><th>Max</th></tr></thead>
              <tbody>
              <?php if (empty($rooms)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:20px">No available rooms</td></tr>
              <?php else: ?>
              <?php foreach ($rooms as $rm): ?>
                <tr>
                  <td><input type="radio" name="room_id" value="<?= $rm['id'] ?>" <?= $values['room_id']==$rm['id']?'checked':'' ?> required></td>
                  <td><strong><?= e($rm['room_number']) ?></strong></td>
                  <td><?= e($rm['type_name']) ?></td>
                  <td><?= $rm['floor'] ?></td>
                  <td><?= formatMoney((float)$rm['base_price']) ?></td>
                  <td><?= $rm['max_occupancy'] ?></td>
                </tr>
              <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-12">
    <button type="submit" class="btn btn-primary">Create Booking</button>
    <a href="./" class="btn btn-ghost">Cancel</a>
  </div>
</form>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
