<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Room Scheduling Tracker') ?> — Room Scheduling Tracker</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<?php
$loggedIn = \App\Core\Auth::check(); $isAdmin = \App\Core\Auth::isAdmin();
// Active nav item, derived from the URL so no controller has to pass it.
$current = $current ?? '';
if ($current === '') {
    $reqPath = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $base = trim(base_path(), '/');
    if ($base !== '' && str_starts_with($reqPath, $base)) { $reqPath = trim(substr($reqPath, strlen($base)), '/'); }
    $seg = explode('/', $reqPath)[0] ?? '';
    $current = match ($seg) { '', 'index.php' => 'dashboard', 'maps' => 'maps', 'rooms' => 'rooms', 'schedules' => 'schedules', 'calendar' => 'calendar', 'availability' => 'availability', 'occurrences' => 'occurrences', 'users' => 'users', 'profile' => 'profile', default => $seg };
}
?>
<?php if ($loggedIn): ?>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="<?= url('') ?>"><span class="brand-mark">R</span> Room Scheduling<br>Tracker</a>
    <nav>
      <a href="<?= url('') ?>" class="<?= $current === 'dashboard' ? 'active' : '' ?>"><span class="nav-icon">🏠</span> Dashboard</a>
      <p class="nav-section">Campus Map</p>
      <a href="<?= url('maps') ?>" class="<?= $current === 'maps' ? 'active' : '' ?>"><span class="nav-icon">🗺️</span> Campus Map</a>
      <a href="<?= url('rooms') ?>" class="<?= $current === 'rooms' ? 'active' : '' ?>"><span class="nav-icon">🚪</span> Rooms</a>
      <p class="nav-section">Schedule</p>
      <a href="<?= url('schedules') ?>" class="<?= $current === 'schedules' ? 'active' : '' ?>"><span class="nav-icon">📅</span> Schedules</a>
      <a href="<?= url('calendar') ?>" class="<?= $current === 'calendar' ? 'active' : '' ?>"><span class="nav-icon">🗓️</span> Calendar</a>
      <a href="<?= url('availability') ?>" class="<?= $current === 'availability' ? 'active' : '' ?>"><span class="nav-icon">🔍</span> Availability</a>
      <a href="<?= url('occurrences') ?>" class="<?= $current === 'occurrences' ? 'active' : '' ?>"><span class="nav-icon">📌</span> Occurrences</a>
      <?php if ($isAdmin): ?>
      <p class="nav-section">Admin</p>
      <a href="<?= url('users') ?>" class="<?= $current === 'users' ? 'active' : '' ?>"><span class="nav-icon">👥</span> User List</a>
      <?php endif; ?>
      <p class="nav-section">Account</p>
      <a href="<?= url('profile') ?>" class="<?= $current === 'profile' ? 'active' : '' ?>"><span class="nav-icon">👤</span> My Profile</a>
    </nav>
    <div class="sidebar-foot">
      <span class="user-chip"><?= e(\App\Core\Auth::user()['username'] ?? '') ?> <small><?= e(\App\Core\Auth::role() ?? '') ?><?= \App\Core\Auth::role() === 'officer' ? ' (org officer)' : '' ?></small></span>
      <form method="post" action="<?= url('logout') ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">⇦ Log out</button></form>
    </div>
  </aside>
  <div class="main-col">
    <header class="topbar">
      <p class="page-title"><?= e($title ?? 'Room Scheduling Tracker') ?></p>
      <div class="top-actions">
        <?php if (\App\Core\Auth::canBook()): ?><a class="btn btn-primary" href="<?= url('schedules/create') ?>">+ New schedule</a><?php endif; ?>
        <span class="muted"><?= e(date('l, F j, Y')) ?></span>
      </div>
    </header>
    <main class="content">
      <?php foreach (flashes() as $f): ?>
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
      <?= $content ?? '' ?>
    </main>
    <footer class="footer">Room Scheduling Tracker — database: <code>rst_db</code></footer>
  </div>
</div>
<?php else: ?>
<header class="guest-topbar">
  <a class="brand" href="<?= url('') ?>"><span class="brand-mark">R</span> Room Scheduling Tracker</a>
  <nav>
    <a href="<?= url('login') ?>">Log in</a>
    &nbsp;·&nbsp;
    <a href="<?= url('register') ?>">Register</a>
  </nav>
</header>
<main>
  <?php foreach (flashes() as $f): ?>
    <div style="max-width:1180px;margin:1rem auto 0;padding:0 1.5rem;"><div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div></div>
  <?php endforeach; ?>
  <?= $content ?? '' ?>
</main>
<footer class="footer">Room Scheduling Tracker — database: <code>rst_db</code></footer>
<?php endif; ?>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?php if (!empty($extra_js)): ?>
<script src="<?= url($extra_js) ?>"></script>
<?php endif; ?>
</body>
</html>
