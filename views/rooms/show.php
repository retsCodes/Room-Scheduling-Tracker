<div class="page-head">
  <h1><?= e($room['room_name']) ?></h1>
  <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn" href="<?= url('rooms/' . (int) $room['room_id'] . '/edit') ?>">Edit room</a><?php endif; ?>
</div>
<p class="muted"><?= e($room['map_name']) ?> · Floor <?= (int) $room['floor_number'] ?> · <?= e($room['shape_type']) ?> · <?= status_badge($room['room_status']) ?></p>
<?php if (!empty($room['room_description'])): ?><p><?= e($room['room_description']) ?></p><?php endif; ?>
<p><a class="btn btn-primary" href="<?= url('schedules/create') ?>">Book this room</a> <a class="btn" href="<?= url('maps/' . (int) $room['map_id']) ?>">View floor map →</a></p>

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
