<?php $isGuest = !\App\Core\Auth::check(); ?>
<?php if ($isGuest): ?>
<div class="page-head">
  <div>
    <h1>📍 Campus Map — <?= e($first_map['map_name'] ?? 'First Floor') ?></h1>
    <p class="muted">See which rooms are free right now, and what's booked today. Log in as a student to check the full schedule.</p>
  </div>
  <a class="btn btn-primary" href="<?= url('login') ?>">Student log in →</a>
</div>
<?php else: ?>
<div class="stat-grid">
  <div class="stat-card"><div class="stat-icon">🚪</div><div><strong><?= (int) $stats['rooms'] ?></strong><span>Rooms</span></div></div>
  <div class="stat-card tint-green"><div class="stat-icon">✅</div><div><strong><?= (int) $stats['free_now'] ?></strong><span>Free right now</span></div></div>
  <div class="stat-card tint-red"><div class="stat-icon">🔴</div><div><strong><?= (int) $stats['busy_now'] ?></strong><span>Occupied now</span></div></div>
  <div class="stat-card tint-amber"><div class="stat-icon">📅</div><div><strong><?= (int) $stats['today'] ?></strong><span>Bookings today</span></div></div>
</div>
<?php endif; ?>

<?php if (!empty($first_map)): ?>
<?php
  $map = $first_map; $rooms = $first_rooms; $show_editor_link = true;
  require __DIR__ . '/../maps/_interactive_map.php';
?>
<?php else: ?>
<section class="card"><p class="muted">No floor maps yet.<?php if (\App\Core\Auth::isAdmin()): ?> <a href="<?= url('maps/create') ?>">Create the first map</a>.<?php endif; ?></p></section>
<?php endif; ?>

<div class="grid-2" style="margin-top:1.1rem">
  <section class="card">
    <h2>Rooms right now</h2>
    <?php if (empty($room_status)): ?><p class="muted">No active rooms yet.</p>
    <?php else: ?>
    <ul class="clean-list">
      <?php foreach ($room_status as $rs): ?>
        <li>
          <span class="dot <?= $rs['free'] ? 'dot-free' : 'dot-busy' ?>"></span>
          <a href="<?= url('rooms/' . (int) $rs['room']['room_id']) ?>"><?= e($rs['room']['room_name']) ?></a>
          <span class="muted"><?= e($rs['room']['map_name']) ?> — <?= $rs['free'] ? 'Free now' : 'Busy now' ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <p><a class="btn" href="<?= url('availability') ?>">Check availability →</a></p>
  </section>

  <section class="card">
    <h2>Today's bookings</h2>
    <?php if (empty($today)): ?><p class="muted">Nothing booked today.</p>
    <?php else: ?>
    <ul class="clean-list">
      <?php foreach ($today as $occ): ?>
        <li>
          <strong><?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?></strong>
          <a href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>"><?= e($occ['schedule_title']) ?></a>
          — <?= e($occ['room_name']) ?> <?= status_badge($occ['occurrence_status']) ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if (\App\Core\Auth::canBook()): ?>
      <p><a class="btn btn-primary" href="<?= url('schedules/create') ?>">+ New schedule</a></p>
    <?php elseif ($isGuest): ?>
      <p><a class="btn btn-primary" href="<?= url('login') ?>">Log in to check schedules →</a></p>
    <?php else: ?>
      <p class="muted">Students view schedules only — booking is for admins, teachers, class mayors and org officers.</p>
    <?php endif; ?>
  </section>
</div>

<?php if (!$isGuest): ?>
<section class="card">
  <h2>Upcoming</h2>
  <?php if (empty($upcoming)): ?><p class="muted">No upcoming bookings.</p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>Date</th><th>Time</th><th>Schedule</th><th>Room</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($upcoming as $occ): ?>
      <tr>
        <td><?= e(date('D, M j', strtotime($occ['occurrence_date']))) ?></td>
        <td><?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?></td>
        <td><a href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>"><?= e($occ['schedule_title']) ?></a></td>
        <td><?= e($occ['room_name']) ?> (<?= e($occ['map_name']) ?>)</td>
        <td><?= status_badge($occ['occurrence_status']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>
<?php endif; ?>
