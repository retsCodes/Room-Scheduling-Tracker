<h1>Check availability</h1>
<p class="muted">Pick a date and time to see which rooms are free. Back-to-back bookings don't conflict — a room ending at 10:00 is free from 10:00.</p>
<form method="get" action="<?= url('availability') ?>" class="card form">
  <div class="grid-2">
    <label>Date <input type="date" name="date" required value="<?= e($date) ?>"></label>
    <label>Floor map
      <select name="map_id"><option value="">All maps</option>
      <?php foreach ($maps as $m): ?><option value="<?= (int) $m['map_id'] ?>" <?= $map_id === (int) $m['map_id'] ? 'selected' : '' ?>><?= e($m['map_name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label>Start time <input type="time" name="start_time" required value="<?= e($start) ?>"></label>
    <label>End time <input type="time" name="end_time" required value="<?= e($end) ?>"></label>
  </div>
  <button class="btn btn-primary" type="submit">Check rooms</button>
</form>

<?php if ($searched): ?>
  <h2>Free rooms — <?= e(date('D, M j, Y', strtotime($date))) ?>, <?= time_12h($start) ?>–<?= time_12h($end) ?></h2>
  <?php if (empty($free)): ?>
    <p class="muted">No rooms are free at that time. Try another time or date.</p>
  <?php else: ?>
    <div class="grid-2">
      <?php foreach ($free as $r): ?>
      <div class="card">
        <h3><?= e($r['room_name']) ?></h3>
        <p class="muted"><?= e($r['map_name']) ?> · <?= e($r['room_description'] ?? '') ?></p>
        <?php if (\App\Core\Auth::canBook()): ?><a class="btn btn-primary" href="<?= url('schedules/create') ?>">Book this room →</a><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php $busy = array_filter($rooms, fn($r) => !in_array($r['room_id'], array_column($free, 'room_id'), false)); ?>
  <?php if (!empty($busy)): ?>
    <h3>Busy then</h3>
    <ul class="clean-list"><?php foreach ($busy as $r): ?><li><?= e($r['room_name']) ?> — <?= e($r['map_name']) ?></li><?php endforeach; ?></ul>
  <?php endif; ?>
<?php endif; ?>
