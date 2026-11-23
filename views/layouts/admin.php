<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Admin') ?> - Admin - Room Scheduling Tracker</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<?php
$loggedIn = \App\Core\Auth::check(); $isAdmin = \App\Core\Auth::isAdmin();

$current = $current ?? '';
if ($current === '') {
    $reqPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $base = trim(base_path(), '/');
    if ($base !== '' && str_starts_with($reqPath, $base)) { $reqPath = trim(substr($reqPath, strlen($base)), '/'); }
    $seg = explode('/', $reqPath)[0] ?? '';
    $current = match ($seg) { 'admin' => 'admin', 'maps' => 'maps', 'rooms' => 'rooms', 'schedules' => 'schedules', 'calendar' => 'calendar', 'availability' => 'availability', 'occurrences' => 'occurrences', 'users' => 'users', 'profile' => 'profile', default => $seg };
}
?>
<div class="app-shell">
  <aside class="sidebar admin">
    <a class="brand" href="<?= url('admin') ?>"><span class="brand-mark">R</span> Room Scheduling<br>Tracker <span class="admin-badge">ADMIN</span></a>
    <nav>
      <a href="<?= url('admin') ?>" class="<?= $current === 'admin' ? 'active' : '' ?>"><span class="nav-icon">📊</span> Dashboard</a>
      <p class="nav-section">Manage</p>
      <a href="<?= url('maps') ?>" class="<?= $current === 'maps' ? 'active' : '' ?>"><span class="nav-icon">🗺️</span> Maps</a>
      <a href="<?= url('rooms') ?>" class="<?= $current === 'rooms' ? 'active' : '' ?>"><span class="nav-icon">🚪</span> Rooms</a>
      <a href="<?= url('schedules') ?>" class="<?= $current === 'schedules' ? 'active' : '' ?>"><span class="nav-icon">📅</span> Schedules</a>
      <a href="<?= url('occurrences') ?>" class="<?= $current === 'occurrences' ? 'active' : '' ?>"><span class="nav-icon">📌</span> Occurrences</a>
      <p class="nav-section">People</p>
      <a href="<?= url('users') ?>" class="<?= $current === 'users' ? 'active' : '' ?>"><span class="nav-icon">👥</span> User List</a>
      <p class="nav-section">Account</p>
      <a href="<?= url('profile') ?>" class="<?= $current === 'profile' ? 'active' : '' ?>"><span class="nav-icon">👤</span> My Profile</a>
    </nav>
    <div class="sidebar-foot">
      <a class="btn" href="<?= url('') ?>" style="width:100%;text-align:center;margin-bottom:.6rem">← Back to user view</a>
      <span class="user-chip"><?= e(\App\Core\Auth::user()['username'] ?? '') ?> <small>admin</small></span>
      <form method="post" action="<?= url('logout') ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">⇦ Log out</button></form>
    </div>
  </aside>
  <div class="main-col">
    <header class="topbar">
      <p class="page-title"><?= e($title ?? 'Admin') ?> <span class="admin-badge">ADMIN</span></p>
      <div class="top-actions">
        <span class="muted"><?= e(date('l, F j, Y')) ?></span>
      </div>
    </header>
    <main class="content">
      <?php foreach (flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?? '' ?>
    </main>
    <footer class="footer">Room Scheduling Tracker - Admin</footer>
  </div>
</div>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Are you sure?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body"><p id="confirmModalText" style="margin:0"></p></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmModalYes">Yes, do it</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $assetVer = function (string $rel): string { $p = dirname(__DIR__, 2) . '/' . $rel; return url($rel) . '?v=' . (is_file($p) ? (string) filemtime($p) : '1'); }; ?>
<script src="<?= $assetVer('assets/js/app.js') ?>"></script>
<?php if (!empty($extra_js)): ?>
<script src="<?= $assetVer($extra_js) ?>"></script>
<?php endif; ?>
</body>
</html>
