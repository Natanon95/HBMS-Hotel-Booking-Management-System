<?php
$id = (int)($_GET['id'] ?? 0);
$guest = Database::queryOne("SELECT * FROM guests WHERE id=?", [$id]);
if (!$guest) { http_response_code(404); die('Guest not found'); }

$pageTitle = 'Edit Guest';
require_once __DIR__ . '/../../includes/header.php';

$errors = [];
$v = $guest;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fields = ['first_name','last_name','email','phone','id_type','id_number','nationality','address','notes'];
    foreach ($fields as $f) $v[$f] = $_POST[$f] ?? '';
    if (empty($v['first_name'])) $errors[] = 'First name required.';
    if (empty($v['last_name']))  $errors[] = 'Last name required.';
    if (empty($errors)) {
        Database::execute("UPDATE guests SET first_name=?,last_name=?,email=?,phone=?,id_type=?,id_number=?,nationality=?,address=?,notes=? WHERE id=?",
            [$v['first_name'],$v['last_name'],$v['email']?:null,$v['phone']?:null,
             $v['id_type'],$v['id_number']?:null,$v['nationality']?:null,$v['address']?:null,$v['notes']?:null,$id]);
        flash('success', 'Guest updated.');
        redirect("/modules/guests/view.php?id={$id}");
    }
}
?>
<div class="page-header">
  <a href="view.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">← Back</a>
  <h1>Edit Guest</h1>
</div>
<?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= e($e) ?></div><?php endforeach; ?>
<div class="card" style="max-width:640px">
  <div class="card-body">
    <form method="post">
      <?= csrfField() ?>
      <div class="form-row">
        <div class="form-group"><label class="form-label">First Name *</label>
          <input name="first_name" class="form-control" value="<?= e($v['first_name']) ?>" required>
        </div>
        <div class="form-group"><label class="form-label">Last Name *</label>
          <input name="last_name" class="form-control" value="<?= e($v['last_name']) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" value="<?= e($v['email']??'') ?>">
        </div>
        <div class="form-group"><label class="form-label">Phone</label>
          <input name="phone" class="form-control" value="<?= e($v['phone']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">ID Type</label>
          <select name="id_type" class="form-control">
            <?php foreach (['national_id','passport','driver_license','other'] as $t): ?>
              <option value="<?= $t ?>" <?= ($v['id_type']??'')===$t?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$t)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label class="form-label">ID Number</label>
          <input name="id_number" class="form-control" value="<?= e($v['id_number']??'') ?>">
        </div>
      </div>
      <div class="form-group"><label class="form-label">Nationality</label>
        <input name="nationality" class="form-control" value="<?= e($v['nationality']??'') ?>">
      </div>
      <div class="form-group"><label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2"><?= e($v['address']??'') ?></textarea>
      </div>
      <div class="form-group"><label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"><?= e($v['notes']??'') ?></textarea>
      </div>
      <div class="d-flex gap-12">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="view.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
