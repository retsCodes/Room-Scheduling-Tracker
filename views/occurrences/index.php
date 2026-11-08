<h1>Occurrences</h1>
<form method="get" action="<?= url('occurrences') ?>" class="card form-inline">
  <label>From <input type="date" name="from" value="<?= e($from) ?>"></label>
  <label>To <input type="date" name="to" value="<?= e($to) ?>"></label>
  <button class="btn" type="submit">Show</button>
</form>
<?php if (empty($occurrences)): ?><p class="muted">No occurrences in this range.</p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Date</th><th>Time</th><th>Schedule</th><th>Room</th><th>Organizer</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($occurrences as $occ): ?>
    <tr>
      <td><?= e(date('D, M j, Y', strtotime($occ['occurrence_date']))) ?></td>
      <td><?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?></td>
      <td><a href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>"><?= e($occ['schedule_title']) ?></a></td>
      <td><?= e($occ['room_name']) ?></td>
      <td><?= e($occ['organizer_name']) ?></td>
      <td><?= status_badge($occ['occurrence_status']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
