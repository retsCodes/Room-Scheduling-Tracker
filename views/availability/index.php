<h1>Check availability</h1>
<p class="muted">Pick a date and time to see every room, what is already booked there, and whether an admin has confirmed it. Rooms with bookings can still be booked — an admin confirms which one goes ahead.</p>
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
  <h2>Rooms — <?= e(date('D, M j, Y', strtotime($date))) ?>, <?= time_12h($start) ?>–<?= time_12h($end) ?></h2>
  <?php if (empty($rooms)): ?>
    <p class="muted">No rooms on this floor.</p>
  <?php else: ?>
    <div class="grid-2">
      <?php foreach ($rooms as $entry):
        $r = $entry['room'];
        $bookUrl = url('schedules/create?room_id=' . (int) $r['room_id'] . '&date=' . e($date) . '&start_time=' . e($start) . '&end_time=' . e($end));
      ?>
      <div class="card">
        <div class="room-card-top">
          <h3 style="margin:0"><?= e($r['room_name']) ?></h3>
          <?= $entry['free'] ? '<span class="badge badge-green">● FREE</span>' : '<span class="badge badge-blue">● BOOKED</span>' ?>
        </div>
        <p class="muted"><?= e($r['map_name']) ?> · <?= e($r['room_description'] ?? '') ?></p>
        <?php if (!empty($entry['bookings'])): ?>
          <?php foreach ($entry['bookings'] as $b): ?>
          <p style="margin:.3rem 0">
            <a href="<?= url('schedules/' . (int) $b['schedule_id']) ?>"><?= e($b['schedule_title']) ?></a>
            <?= time_12h($b['start_time']) ?>–<?= time_12h($b['end_time']) ?> · <?= e($b['organizer_name']) ?>
            <?= confirm_badge($b['admin_confirmed'] ?? 0) ?>
          </p>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (\App\Core\Auth::canBook()): ?><p style="margin-top:.7rem"><a class="btn btn-primary" href="<?= $bookUrl ?>">Book this room →</a></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>
