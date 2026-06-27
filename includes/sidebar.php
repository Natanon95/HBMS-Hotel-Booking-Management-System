<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function navItem(string $href, string $label, string $icon, string $badge = ''): void {
    global $currentPath;
    $active = str_contains($currentPath, parse_url($href, PHP_URL_PATH)) ? ' active' : '';
    $badgeHtml = $badge ? "<span class=\"nav-badge\">{$badge}</span>" : '';
    echo "<a href=\"{$href}\" class=\"nav-item{$active}\">{$icon} {$label}{$badgeHtml}</a>";
}

// Count today's pending check-ins
try {
    $arrivals = Database::queryOne(
        "SELECT COUNT(*) c FROM bookings WHERE status='confirmed' AND check_in=CURDATE()"
    )['c'] ?? 0;
} catch (Exception) { $arrivals = 0; }
?>
<nav class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
      <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    <?= APP_NAME ?>
  </div>

  <div class="sidebar-nav">
    <div class="nav-section">Main</div>
    <?php navItem(BASE_URL.'/modules/dashboard/', 'Dashboard',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>'); ?>
    <?php navItem(BASE_URL.'/modules/bookings/', 'Bookings',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>',
      $arrivals > 0 ? (string)$arrivals : ''); ?>
    <?php navItem(BASE_URL.'/modules/rooms/', 'Rooms',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>'); ?>
    <?php navItem(BASE_URL.'/modules/guests/', 'Guests',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'); ?>

    <div class="nav-section">Finance</div>
    <?php navItem(BASE_URL.'/modules/payments/', 'Payments',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>'); ?>
    <?php navItem(BASE_URL.'/modules/reports/', 'Reports',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>'); ?>

    <div class="nav-section">Operations</div>
    <?php navItem(BASE_URL.'/modules/housekeeping/', 'Housekeeping',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3z"/><path d="M3 9h18M9 21V9"/></svg>'); ?>

    <?php if (Auth::isAdmin()): ?>
    <div class="nav-section">Admin</div>
    <?php navItem(BASE_URL.'/modules/settings/', 'Settings',
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'); ?>
    <?php endif; ?>
  </div>

  <div class="sidebar-footer">
    v<?= APP_VERSION ?> &nbsp;·&nbsp; <?= date('d M Y') ?>
  </div>
</nav>
