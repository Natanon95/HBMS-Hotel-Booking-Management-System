<?php
$id = (int)($_GET['id'] ?? 0);
$booking = Database::queryOne("
    SELECT b.*, g.first_name, g.last_name, g.email AS guest_email, g.phone AS guest_phone,
           r.room_number, rt.name AS room_type
    FROM bookings b
    JOIN guests g    ON g.id  = b.guest_id
    JOIN rooms  r    ON r.id  = b.room_id
    JOIN room_types rt ON rt.id = r.room_type_id
    WHERE b.id=?", [$id]);

if (!$booking) { http_response_code(404); die('Booking not found'); }
$pageTitle = 'Booking ' . $booking['booking_ref'];
require_once __DIR__ . '/../../includes/header.php';

$payments = Database::query("SELECT * FROM payments WHERE booking_id=? ORDER BY created_at", [$id]);
$extras   = Database::query("SELECT * FROM booking_extras WHERE booking_id=? ORDER BY created_at", [$id]);
$paidTotal = array_sum(array_column(array_filter($payments, fn($p) => $p['status']==='completed'), 'amount'));

$statusSteps = ['pending','confirmed','checked_in','checked_out'];
$cancelledStatuses = ['cancelled','no_show'];
$currentStatus = $booking['status'];
?>

<div class="page-header">
  <a href="./" class="btn btn-ghost btn-sm">← Back</a>
  <h1><?= e($booking['booking_ref']) ?></h1>
  <?= statusBadge($currentStatus) ?>
  <div class="d-flex gap-8 ml-auto">
    <?php if ($currentStatus === 'pending'): ?>
      <a href="update_status.php?id=<?= $id ?>&status=confirmed" class="btn btn-success btn-sm"
         data-confirm="Confirm this booking?">Confirm</a>
    <?php elseif ($currentStatus === 'confirmed'): ?>
      <a href="checkin.php?id=<?= $id ?>" class="btn btn-success btn-sm">Check In</a>
    <?php elseif ($currentStatus === 'checked_in'): ?>
      <a href="checkout.php?id=<?= $id ?>" class="btn btn-primary btn-sm">Check Out</a>
    <?php endif; ?>
    <?php if (!in_array($currentStatus, ['checked_out','cancelled','no_show'])): ?>
      <a href="cancel.php?id=<?= $id ?>" class="btn btn-danger btn-sm"
         data-confirm="Cancel this booking? This cannot be undone easily.">Cancel</a>
    <?php endif; ?>
  </div>
</div>

<!-- Status timeline -->
<?php if (!in_array($currentStatus, $cancelledStatuses)): ?>
<div class="card mb-24">
  <div class="card-body">
    <div class="timeline">
      <?php
      $reached = false;
      foreach ($statusSteps as $i => $step):
        $isDone   = array_search($step, $statusSteps) < array_search($currentStatus, $statusSteps);
        $isActive = $step === $currentStatus;
        $dotClass = $isDone ? 'done' : ($isActive ? 'active' : '');
        if ($i > 0): ?><div class="tl-line <?= $isDone ? 'done' : '' ?>"></div><?php endif; ?>
        <div class="tl-step">
          <div class="tl-dot <?= $dotClass ?>"><?= $isDone ? '✓' : ($i+1) ?></div>
          <div class="tl-label"><?= ucfirst(str_replace('_',' ',$step)) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="grid-2">
  <!-- Booking details -->
  <div>
    <div class="card mb-16">
      <div class="card-header"><span class="card-title">Booking Details</span></div>
      <div class="card-body">
        <table class="w-100" style="border:none">
          <tr><td class="text-muted text-sm" style="width:140px;padding:5px 0">Room</td><td><?= e($booking['room_number']) ?> — <?= e($booking['room_type']) ?></td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Check-in</td><td><?= formatDate($booking['check_in']) ?></td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Check-out</td><td><?= formatDate($booking['check_out']) ?></td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Nights</td><td><?= nights($booking['check_in'],$booking['check_out']) ?></td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Guests</td><td><?= $booking['adults'] ?> adult<?= $booking['adults']>1?'s':'' ?><?= $booking['children'] ? ', '.$booking['children'].' child'.($booking['children']>1?'ren':'') : '' ?></td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Source</td><td><?= ucfirst(str_replace('_',' ',$booking['source'])) ?></td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Room Rate</td><td><?= formatMoney((float)$booking['room_rate']) ?>/night</td></tr>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Room Total</td><td><?= formatMoney((float)$booking['room_rate'] * nights($booking['check_in'],$booking['check_out'])) ?></td></tr>
          <?php if (!empty($booking['special_requests'])): ?>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Requests</td><td><?= e($booking['special_requests']) ?></td></tr>
          <?php endif; ?>
          <tr><td class="text-muted text-sm" style="padding:5px 0">Created</td><td><?= formatDateTime($booking['created_at']) ?></td></tr>
        </table>
      </div>
    </div>

    <div class="card mb-16">
      <div class="card-header"><span class="card-title">Guest</span></div>
      <div class="card-body">
        <a href="<?= BASE_URL ?>/modules/guests/view.php?id=<?= $booking['guest_id'] ?>" class="fw-600">
          <?= e($booking['first_name'].' '.$booking['last_name']) ?>
        </a>
        <?php if ($booking['guest_email']): ?>
          <div class="text-sm text-muted mt-16"><?= e($booking['guest_email']) ?></div>
        <?php endif; ?>
        <?php if ($booking['guest_phone']): ?>
          <div class="text-sm text-muted"><?= e($booking['guest_phone']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Financial -->
  <div>
    <div class="card mb-16">
      <div class="card-header">
        <span class="card-title">Extras</span>
        <?php if (!in_array($currentStatus, ['checked_out','cancelled','no_show'])): ?>
          <button class="btn btn-sm btn-outline" data-modal-open="addExtraModal">+ Add Extra</button>
        <?php endif; ?>
      </div>
      <div class="card-body" style="padding:0">
        <?php if (empty($extras)): ?>
          <div style="padding:16px;color:var(--text-muted);font-size:.85rem">No extras added.</div>
        <?php else: ?>
          <table>
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($extras as $ex): ?>
              <tr>
                <td><?= e($ex['name']) ?></td>
                <td><?= $ex['qty'] ?></td>
                <td><?= formatMoney((float)$ex['unit_price']) ?></td>
                <td><?= formatMoney((float)$ex['total_price']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-16">
      <div class="card-header">
        <span class="card-title">Payments</span>
        <?php if (!in_array($currentStatus, ['cancelled','no_show'])): ?>
          <button class="btn btn-sm btn-outline" data-modal-open="addPaymentModal">+ Payment</button>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <?php foreach ($payments as $pay): ?>
          <div class="d-flex align-center gap-8 mb-16">
            <?= statusBadge($pay['status']) ?>
            <span><?= formatMoney((float)$pay['amount']) ?> · <?= ucfirst(str_replace('_',' ',$pay['method'])) ?></span>
            <span class="text-sm text-muted ml-auto"><?= $pay['paid_at'] ? formatDate($pay['paid_at']) : '—' ?></span>
          </div>
        <?php endforeach; ?>
        <div class="divider"></div>
        <div class="d-flex align-center">
          <span class="text-muted text-sm">Extras Total</span>
          <span class="ml-auto"><?= formatMoney(array_sum(array_column($extras,'total_price'))) ?></span>
        </div>
        <div class="d-flex align-center mt-16">
          <span class="fw-600">Grand Total</span>
          <span class="fw-700 ml-auto text-primary"><?= formatMoney((float)$booking['total_amount'] + array_sum(array_column($extras,'total_price'))) ?></span>
        </div>
        <div class="d-flex align-center mt-16">
          <span class="text-muted text-sm">Paid</span>
          <span class="ml-auto text-success fw-600"><?= formatMoney($paidTotal) ?></span>
        </div>
        <?php $balance = ((float)$booking['total_amount'] + array_sum(array_column($extras,'total_price'))) - $paidTotal; ?>
        <?php if ($balance > 0): ?>
        <div class="d-flex align-center mt-16">
          <span class="text-muted text-sm">Balance Due</span>
          <span class="ml-auto text-danger fw-600"><?= formatMoney($balance) ?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Add Extra Modal -->
<div class="modal-overlay hidden" id="addExtraModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Add Extra</span>
      <button class="btn-icon" data-modal-close="addExtraModal">✕</button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/api/extras.php">
      <?= csrfField() ?>
      <input type="hidden" name="booking_id" value="<?= $id ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Item Name</label>
          <input name="name" class="form-control" required placeholder="e.g. Breakfast, Spa">
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Qty</label>
            <input name="qty" type="number" class="form-control" value="1" min="1">
          </div>
          <div class="form-group"><label class="form-label">Unit Price (฿)</label>
            <input name="unit_price" type="number" class="form-control" value="0" min="0" step="0.01">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="addExtraModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Extra</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Payment Modal -->
<div class="modal-overlay hidden" id="addPaymentModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Record Payment</span>
      <button class="btn-icon" data-modal-close="addPaymentModal">✕</button>
    </div>
    <form method="post" action="<?= BASE_URL ?>/api/payments.php">
      <?= csrfField() ?>
      <input type="hidden" name="booking_id" value="<?= $id ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Amount (฿)</label>
          <input name="amount" type="number" class="form-control" value="<?= number_format($balance, 2, '.', '') ?>" min="0.01" step="0.01" required>
        </div>
        <div class="form-group"><label class="form-label">Method</label>
          <select name="method" class="form-control">
            <?php foreach (['cash','credit_card','debit_card','bank_transfer','online'] as $m): ?>
              <option value="<?= $m ?>"><?= ucfirst(str_replace('_',' ',$m)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Reference</label>
          <input name="reference" class="form-control" placeholder="Receipt / transaction ref">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="addPaymentModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Payment</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
