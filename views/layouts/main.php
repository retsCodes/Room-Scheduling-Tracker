<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Room Scheduling Tracker') ?> — Room Scheduling Tracker</title>
<link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= url('') ?>">🏫 Room Scheduling Tracker</a>
  <nav>
    <?php if (\App\Core\Auth::check()): ?>
      <a href="<?= url('') ?>">Dashboard</a>
      <a href="<?= url('maps') ?>">Maps</a>
      <a href="<?= url('rooms') ?>">Rooms</a>
      <a href="<?= url('schedules') ?>">Schedules</a>
      <a href="<?= url('calendar') ?>">Calendar</a>
      <a href="<?= url('availability') ?>">Availability</a>
      <a href="<?= url('occurrences') ?>">Occurrences</a>
      <?php if (\App\Core\Auth::isAdmin()): ?><a href="<?= url('users') ?>">Users</a><?php endif; ?>
      <span class="user-chip"><?= e(\App\Core\Auth::user()['username'] ?? '') ?> (<?= e(\App\Core\Auth::role() ?? '') ?>)</span>
      <form method="post" action="<?= url('logout') ?>" class="inline-form"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">Log out</button></form>
    <?php else: ?>
      <a href="<?= url('login') ?>">Log in</a>
      <a href="<?= url('register') ?>">Register</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
  <?php foreach (flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  <?= $content ?? '' ?>
</main>
<footer class="footer">
  Room Scheduling Tracker — database: <code>rst_db</code> (tblUsers, tblMaps, tblRooms, tblSchedules, tblScheduleDays, tblScheduleExceptions, tblScheduleOccurrences)
</footer>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
