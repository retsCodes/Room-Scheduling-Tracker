<div class="page-head">
  <h1><?= e($schedule['schedule_title']) ?></h1>
  <div>
    <a class="btn" href="<?= url('schedules/' . (int) $schedule['schedule_id'] . '/edit') ?>">Edit</a>
    <?php if ($schedule['schedule_status'] === 'active'): ?>
    <form method="post" action="<?= url('schedules/' . (int) $schedule['schedule_id'] . '/cancel') ?>" class="inline-form" onsubmit="return confirm('Cancel this schedule and its future occurrences?')"><?= csrf_field() ?><button class="btn btn-danger" type="submit">Cancel schedule</button></form>
    <?php endif; ?>
  </div>
</div>
<div class="card">
  <p><strong>Room:</strong> <a href="<?= url('rooms/' . (int) $schedule['room_id']) ?>"><?= e($schedule['room_name']) ?></a> (<?= e($schedule['map_name']) ?>)</p>
  <p><strong>Organizer:</strong> <?= e($schedule['organizer_name']) ?></p>
  <p><strong>Recurrence:</strong> <?= e($schedule['recurrence_type']) ?><?php if (!empty($schedule['days'])): ?> — <?= implode(', ', array_map(fn($d) => date('l', strtotime('2026-11-0' . ($d + 1))), $schedule['days'])) ?><?php endif; ?></p>
  <p><strong>Dates:</strong> <?= e($schedule['start_date']) ?><?= $schedule['end_date'] ? ' → ' . e($schedule['end_date']) : '' ?></p>
  <p><strong>Time:</strong> <?= time_12h($schedule['start_time']) ?>–<?= time_12h($schedule['end_time']) ?></p>
  <p><strong>Status:</strong> <?= status_badge($schedule['schedule_status']) ?></p>
  <?php if (!empty($schedule['schedule_description'])): ?><p><?= e($schedule['schedule_description']) ?></p><?php endif; ?>
</div>

<h2>Occurrences</h2>
<?php if (empty($occurrences)): ?><p class="muted">No occurrences generated yet.</p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Date</th><th>Time</th><th>Status</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($occurrences as $occ): ?>
    <tr>
      <td><?= e(date('D, M j, Y', strtotime($occ['occurrence_date']))) ?></td>
      <td><?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?></td>
      <td><?= status_badge($occ['occurrence_status']) ?></td>
      <td>
        <?php if ($occ['occurrence_status'] === 'scheduled'): ?>
        <form method="post" action="<?= url('occurrences/' . (int) $occ['occurrence_id'] . '/status') ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="status" value="cancelled"><button class="btn btn-small" type="submit">Cancel this date</button></form>
        <?php elseif ($occ['occurrence_status'] === 'cancelled'): ?>
        <form method="post" action="<?= url('occurrences/' . (int) $occ['occurrence_id'] . '/status') ?>" class="inline-form"><?= csrf_field() ?><input type="hidden" name="status" value="scheduled"><button class="btn btn-small" type="submit">Restore</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<h2>Add an exception</h2>
<p class="muted">Skip, move or modify a single occurrence without changing the whole series.</p>
<form method="post" action="<?= url('schedules/' . (int) $schedule['schedule_id'] . '/exceptions') ?>" class="card form">
  <?= csrf_field() ?>
  <div class="grid-2">
    <label>Original date <input type="date" name="original_date" required></label>
    <label>Exception type
      <select name="exception_type">
        <option value="skipped">Skipped</option>
        <option value="moved">Moved</option>
        <option value="modified">Modified</option>
      </select>
    </label>
    <label>New date (for moved) <input type="date" name="new_date"></label>
    <label>New room (optional)
      <select name="new_room_id"><option value="">Keep same room</option>
      <?php foreach ($rooms as $r): ?><option value="<?= (int) $r['room_id'] ?>"><?= e($r['room_name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>New start time <input type="time" name="new_start_time"></label>
    <label>New end time <input type="time" name="new_end_time"></label>
  </div>
  <label>Reason <input type="text" name="exception_reason" maxlength="255" placeholder="Room unavailable for maintenance"></label>
  <button class="btn btn-primary" type="submit">Save exception</button>
</form>

<?php if (!empty($exceptions)): ?>
<h2>Exceptions</h2>
<table class="table card">
  <thead><tr><th>Original date</th><th>Type</th><th>New date / room / time</th><th>Reason</th><th>By</th></tr></thead>
  <tbody>
    <?php foreach ($exceptions as $ex): ?>
    <tr>
      <td><?= e($ex['original_date']) ?></td>
      <td><?= status_badge($ex['exception_type']) ?></td>
      <td><?= e($ex['new_date'] ?? '—') ?> <?= e($ex['new_room_name'] ?? '') ?> <?= $ex['new_start_time'] ? time_12h($ex['new_start_time']) . '–' . time_12h($ex['new_end_time']) : '' ?></td>
      <td><?= e($ex['exception_reason'] ?? '') ?></td>
      <td><?= e($ex['created_by_name']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<form method="post" action="<?= url('schedules/' . (int) $schedule['schedule_id'] . '/delete') ?>" class="card form danger-zone" onsubmit="return confirm('Delete this schedule and all its occurrences?')">
  <?= csrf_field() ?><button class="btn btn-danger" type="submit">Delete schedule</button>
</form>
