<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';
Auth::require();
$user = Auth::user();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
  <title><?= e($pageTitle) ?> — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><text y='26' font-size='28'>🏨</text></svg>">
</head>
<body>
<div class="layout">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main-wrap">
<header class="header">
  <button class="btn-icon" id="sidebarToggle" aria-label="Toggle sidebar">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>
  <span class="header-title"><?= e($pageTitle) ?></span>
  <div class="header-actions">
    <?php if (defined('DEMO_MODE') && DEMO_MODE): ?>
      <span class="badge badge-warning">DEMO</span>
    <?php endif; ?>
    <span class="text-sm text-muted"><?= e($user['name']) ?></span>
    <div class="avatar" title="<?= e($user['email']) ?>"><?= e(strtoupper(substr($user['name'],0,1))) ?></div>
    <a href="<?= BASE_URL ?>/logout.php" class="btn-icon" title="Logout">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
    </a>
  </div>
</header>
<main class="content">
<?php
// Flash messages
foreach (['success','danger','warning','info'] as $type) {
    $msg = flash($type);
    if ($msg): ?>
      <div class="alert alert-<?= $type ?>" data-autohide><?= e($msg) ?></div>
    <?php endif;
}
?>
