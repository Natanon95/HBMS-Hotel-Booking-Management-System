<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/helpers.php';

Auth::start();
if (Auth::check()) { redirect('/modules/dashboard/'); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (Auth::login($email, $password)) {
        redirect('/modules/dashboard/');
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <h1>🏨 <?= APP_NAME ?></h1>
      <p>Hotel Property Management System</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="form-control"
               value="<?= e($_POST['email'] ?? '') ?>" required autofocus placeholder="admin@hotel.demo">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input id="password" name="password" type="password" class="form-control" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary w-100" style="justify-content:center">Sign In</button>
    </form>

    <?php if (DEMO_MODE): ?>
    <div class="demo-hint">
      <strong>Demo credentials:</strong><br>
      Admin: <code>admin@hotel.demo</code> / <code>password</code><br>
      Staff: <code>sara@hotel.demo</code> / <code>password</code>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
