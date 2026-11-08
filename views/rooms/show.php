<div class="page-head">
  <h1><?= e($room['room_name']) ?></h1>
  <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn" href="<?= url('rooms/' . (int) $room['room_id'] . '/edit') ?>">Edit room</a><?php endif; ?>
</div>
<p class="muted"><?= e($room['map_name']) ?> · Floor <?= (int) $room['floor_number'] ?> · <?= e($room['shape_type']) ?> · <?= status_badge($room['room_status']) ?></p>
<?php if (!empty($room['room_description'])): ?><p><?= e($room['room_description']) ?></p><?php endif; ?>
<p><?php if (\App\Core\Auth::canBook()): ?><a class="btn btn-primary" href="<?= url('schedules/create') ?>">Book this room</a> <?php endif; ?><a class="btn" href="<?= url('maps/' . (int) $room['map_id']) ?>">View floor map →</a></p>

<?php
// Timeline view (Figma Room Details): today's bookings as bars, 07:00–19:00.
$tlStart = 7 * 60; $tlEnd = 19 * 60; $tlSpan = $tlEnd - $tlStart;
$toMin = fn(string $t): int => (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2);
$tlClass = fn(string $s): string => $s === 'cancelled' ? 'tl-red' : ($s === 'completed' ? 'tl-green' : 'tl-blue');
?>
<section class="card">
  <h2>⏱️ Timeline — today, <?= e(date('l, F j', strtotime($today))) ?></h2>
  <?php if (empty($today_occ)): ?>
    <p class="muted">Nothing booked today — this room is free all day.</p>
  <?php else: ?>
    <div class="timeline">
      <?php foreach ($today_occ as $occ):
        $left = max(0, ($toMin($occ['start_time']) - $tlStart) / $tlSpan * 100);
        $width = max(2, ($toMin($occ['end_time']) - $toMin($occ['start_time'])) / $tlSpan * 100);
      ?>
      <div class="timeline-row">
        <span class="tl-time"><?= time_12h($occ['start_time']) ?></span>
        <div class="timeline-track">
          <a class="timeline-block <?= $tlClass($occ['occurrence_status']) ?>" style="left:<?= round($left, 1) ?>%;width:<?= round(min($width, 100 - $left), 1) ?>%"
             href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>" title="<?= e($occ['schedule_title']) ?> <?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?>"><?= e($occ['schedule_title']) ?></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="timeline-scale"><span>7 AM</span><span>10 AM</span><span>1 PM</span><span>4 PM</span><span>7 PM</span></div>
    <p class="muted" style="margin-top:.6rem"><span class="badge badge-blue">Booked</span> <span class="badge badge-red">Cancelled</span> <span class="badge badge-green">Completed</span></p>
  <?php endif; ?>
</section>

<h2>Upcoming bookings (next 30 days)</h2>
<?php if (empty($upcoming)): ?><p class="muted">No upcoming bookings for this room.</p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Date</th><th>Time</th><th>Schedule</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($upcoming as $occ): ?>
    <tr>
      <td><?= e(date('D, M j, Y', strtotime($occ['occurrence_date']))) ?></td>
      <td><?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?></td>
      <td><a href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>"><?= e($occ['schedule_title']) ?></a></td>
      <td><?= status_badge($occ['occurrence_status']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
