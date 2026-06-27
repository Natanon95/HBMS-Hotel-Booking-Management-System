<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../../includes/header.php';
Auth::requireRole('admin');

$users = Database::query("SELECT * FROM users ORDER BY name");
$roomTypes = Database::query("SELECT * FROM room_types ORDER BY base_price");
$errors = [];

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $role  = $_POST['role'] ?? 'receptionist';
    if (!$name || !$email || !$pass) $errors[] = 'All fields required.';
    if (empty($errors)) {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        try {
            Database::execute("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)", [$name,$email,$hash,$role]);
            flash('success', "User {$name} created.");
        } catch (Exception) { $errors[] = 'Email already exists.'; }
        if (empty($errors)) redirect('/modules/settings/');
    }
}
?>
<div class="page-header"><h1>Settings</h1></div>

<div class="grid-2">
  <!-- Users -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Staff Users</span>
      <button class="btn btn-sm btn-outline" data-modal-open="addUserModal">+ Add User</button>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Last Login</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= statusBadge($u['role']) ?></td>
            <td><?= $u['is_active'] ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
            <td class="text-sm text-muted"><?= $u['last_login'] ? formatDateTime($u['last_login']) : 'Never' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Room types -->
  <div class="card">
    <div class="card-header"><span class="card-title">Room Types</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Type</th><th>Rate/Night</th><th>Max Guests</th></tr></thead>
        <tbody>
        <?php foreach ($roomTypes as $rt): ?>
          <tr>
            <td><?= e($rt['name']) ?></td>
            <td><?= formatMoney((float)$rt['base_price']) ?></td>
            <td><?= $rt['max_occupancy'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay hidden" id="addUserModal">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Add Staff User</span>
      <button class="btn-icon" data-modal-close="addUserModal">✕</button>
    </div>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger" style="margin:16px 20px 0"><?= e($e) ?></div><?php endforeach; ?>
    <form method="post">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create_user">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Full Name</label>
          <input name="name" class="form-control" required>
        </div>
        <div class="form-group"><label class="form-label">Email</label>
          <input name="email" type="email" class="form-control" required>
        </div>
        <div class="form-group"><label class="form-label">Password</label>
          <input name="password" type="password" class="form-control" required minlength="6">
        </div>
        <div class="form-group"><label class="form-label">Role</label>
          <select name="role" class="form-control">
            <option value="receptionist">Receptionist</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-modal-close="addUserModal">Cancel</button>
        <button type="submit" class="btn btn-primary">Create User</button>
      </div>
    </form>
  </div>
</div>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded',()=>openModal('addUserModal'));</script>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
