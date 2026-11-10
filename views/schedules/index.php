<div class="page-head">
  <h1>Schedules</h1>
  <?php if (\App\Core\Auth::canBook()): ?><a class="btn btn-primary" href="<?= url('schedules/create') ?>">+ New schedule</a><?php endif; ?>
</div>
<?php if (\App\Core\Auth::isReadOnly()): ?><p class="muted">You're viewing schedules as a student - only admins, teachers, class mayors and org officers can book rooms.</p><?php endif; ?>
<form method="get" action="<?= url('schedules') ?>" class="card form">
  <div class="grid-2">
    <label>Search title <input type="search" name="search" placeholder="Search title…" value="<?= e($filters['search'] ?? '') ?>"></label>
    <label>Room
      <select name="room_id">
        <option value="">All rooms</option>
        <?php foreach ($rooms as $r): ?>
          <option value="<?= (int) $r['room_id'] ?>" <?= ($filters['room_id'] ?? null) === (int) $r['room_id'] ? 'selected' : '' ?>><?= e($r['room_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Status
      <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['active','cancelled','archived'] as $s): ?>
          <option <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <button class="btn btn-primary" type="submit">Filter</button>
</form>
<?php if (empty($schedules)): ?>
  <p class="muted">No schedules match.<?php if (\App\Core\Auth::canBook()): ?> <a href="<?= url('schedules/create') ?>">Create the first one</a>.<?php endif; ?></p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Title</th><th class="nowrap">Room</th><th>Recurrence</th><th class="nowrap">Dates</th><th class="nowrap">Time</th><th>Organizer</th><th>Status</th><th>Confirmed</th></tr></thead>
  <tbody>
  <?php foreach ($schedules as $s): ?>
    <tr>
      <td><a href="<?= url('schedules/' . (int) $s['schedule_id']) ?>"><?= e($s['schedule_title']) ?></a></td>
      <td class="nowrap"><?= e($s['room_name']) ?></td>
      <td><?= e($s['recurrence_type']) ?><?php if ($s['recurrence_type'] === 'weekly' && !empty($s['days'])): ?> (<?= implode(', ', array_map('day_name', $s['days'])) ?>)<?php endif; ?></td>
      <td class="nowrap"><?= e($s['start_date']) ?><?= $s['end_date'] ? ' → ' . e($s['end_date']) : '' ?></td>
      <td class="nowrap"><?= time_12h($s['start_time']) ?>–<?= time_12h($s['end_time']) ?></td>
      <td><?= e($s['organizer_name']) ?></td>
      <td><?= status_badge($s['schedule_status']) ?></td>
      <td><?= confirm_badge($s['admin_confirmed'] ?? 0) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
