<h1>Dashboard</h1>
<p class="muted">Today is <?= e(date('l, F j, Y')) ?>.</p>

<div class="stat-grid">
  <div class="stat"><strong><?= (int) $stats['rooms'] ?></strong><span>Rooms</span></div>
  <div class="stat"><strong><?= (int) $stats['maps'] ?></strong><span>Floor maps</span></div>
  <div class="stat"><strong><?= (int) $stats['schedules'] ?></strong><span>Active schedules</span></div>
  <div class="stat"><strong><?= (int) $stats['today'] ?></strong><span>Bookings today</span></div>
</div>

<div class="grid-2">
  <section class="card">
    <h2>Rooms right now</h2>
    <?php if (empty($room_status)): ?><p class="muted">No active rooms yet. <a href="<?= url('rooms/create') ?>">Add one</a>.</p>
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
    <p><a class="btn btn-primary" href="<?= url('schedules/create') ?>">+ New schedule</a></p>
  </section>
</div>

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

<?php if (!empty($maps)): ?>
<section class="card">
  <h2>Floor maps</h2>
  <p><?php foreach ($maps as $m): ?><a class="btn" href="<?= url('maps/' . (int) $m['map_id']) ?>"><?= e($m['map_name']) ?></a> <?php endforeach; ?></p>
</section>
<?php endif; ?>
