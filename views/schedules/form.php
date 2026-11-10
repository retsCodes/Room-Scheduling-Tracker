<h1><?= $schedule ? 'Edit schedule' : 'New schedule' ?></h1>
<p class="muted">Overlapping bookings are allowed - an admin confirms which one goes ahead.</p>
<form method="post" action="<?= $schedule ? url('schedules/' . (int) $schedule['schedule_id']) : url('schedules') ?>" class="card form">
  <?= csrf_field() ?>
  <label>Title
    <input type="text" name="schedule_title" required maxlength="150" value="<?= e($schedule['schedule_title'] ?? '') ?>" placeholder="IT Department Meeting">
  </label>
  <label>Description (optional)
    <textarea name="schedule_description" rows="2"><?= e($schedule['schedule_description'] ?? '') ?></textarea>
  </label>
  <label>Room
    <select name="room_id" required>
      <?php foreach ($rooms as $r): ?>
        <option value="<?= (int) $r['room_id'] ?>" <?= (int) ($schedule['room_id'] ?? $prefill['room_id'] ?? 0) === (int) $r['room_id'] ? 'selected' : '' ?>><?= e($r['room_name']) ?> - <?= e($r['map_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Recurrence
    <select name="recurrence_type" id="recurrence_type">
      <?php foreach (['one_time' => 'One time', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly'] as $v => $label): ?>
        <option value="<?= $v ?>" <?= ($schedule['recurrence_type'] ?? 'one_time') === $v ? 'selected' : '' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <fieldset id="weekdays" class="weekdays">
    <legend>Weekdays (weekly only)</legend>
    <?php for ($d = 1; $d <= 7; $d++): ?>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="days[]" id="day-<?= $d ?>" value="<?= $d ?>" <?= in_array($d, $schedule['days'] ?? [], true) ? 'checked' : '' ?>>
        <label class="form-check-label" for="day-<?= $d ?>"><?= day_name($d) ?></label>
      </div>
    <?php endfor; ?>
  </fieldset>
  <div class="grid-2">
    <label>Start date <input type="date" name="start_date" required value="<?= e($schedule['start_date'] ?? $prefill['start_date'] ?? date('Y-m-d')) ?>"></label>
    <label>End date <input type="date" name="end_date" value="<?= e($schedule['end_date'] ?? '') ?>"> <span class="muted">Required for recurring schedules; leave blank for one-time.</span></label>
    <label>Start time <input type="time" name="start_time" required value="<?= e(substr($schedule['start_time'] ?? ($prefill['start_time'] ?? '09:00') . ':00', 0, 5)) ?>"></label>
    <label>End time <input type="time" name="end_time" required value="<?= e(substr($schedule['end_time'] ?? ($prefill['end_time'] ?? '10:00') . ':00', 0, 5)) ?>"></label>
  </div>
  <?php if ($schedule): ?>
  <label>Status
    <select name="schedule_status">
      <?php foreach (['active','cancelled','archived'] as $s): ?>
        <option <?= ($schedule['schedule_status'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <?php endif; ?>
  <button class="btn btn-primary" type="submit"><?= $schedule ? 'Save changes' : 'Create schedule' ?></button>
  <a class="btn" href="<?= url('schedules') ?>">Cancel</a>
</form>
