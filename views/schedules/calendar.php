<?php
$firstTs = strtotime($first);
$year = (int) date('Y', $firstTs);
$monthNum = (int) date('n', $firstTs);
$daysInMonth = (int) date('t', $firstTs);
$firstWeekday = (int) date('N', $firstTs); 
$prev = date('Y-m', strtotime($month . '-01 -1 month'));
$next = date('Y-m', strtotime($month . '-01 +1 month'));
?>
<div class="page-head">
  <h1><?= e(date('F Y', $firstTs)) ?></h1>
  <div>
    <a class="btn" href="<?= url('calendar?month=' . $prev . ($room_id ? '&room_id=' . $room_id : '')) ?>">← Prev</a>
    <a class="btn" href="<?= url('calendar') ?>">Today</a>
    <a class="btn" href="<?= url('calendar?month=' . $next . ($room_id ? '&room_id=' . $room_id : '')) ?>">Next →</a>
  </div>
</div>
<form method="get" action="<?= url('calendar') ?>" class="card form-inline">
  <input type="hidden" name="month" value="<?= e($month) ?>">
  <label>Room
    <select name="room_id" onchange="this.form.submit()">
      <option value="">All rooms</option>
      <?php foreach ($rooms as $r): ?>
        <option value="<?= (int) $r['room_id'] ?>" <?= $room_id === (int) $r['room_id'] ? 'selected' : '' ?>><?= e($r['room_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</form>
<div class="calendar">
  <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?><div class="cal-head"><?= $d ?></div><?php endforeach; ?>
  <?php for ($i = 1; $i < $firstWeekday; $i++): ?><div class="cal-day cal-empty"></div><?php endfor; ?>
  <?php for ($day = 1; $day <= $daysInMonth; $day++):
    $date = sprintf('%04d-%02d-%02d', $year, $monthNum, $day);
    $isToday = $date === date('Y-m-d');
    $events = $by_date[$date] ?? [];
  ?>
    <div class="cal-day <?= $isToday ? 'cal-today' : '' ?>">
      <strong><?= $day ?></strong>
      <?php foreach (array_slice($events, 0, 3) as $ev): ?>
        <a class="cal-event <?= $ev['occurrence_status'] === 'cancelled' ? 'cal-cancelled' : '' ?>" href="<?= url('schedules/' . (int) $ev['schedule_id']) ?>" title="<?= e($ev['schedule_title']) ?> - <?= e($ev['room_name']) ?>">
          <?= substr($ev['start_time'], 0, 5) ?> <?= e($ev['schedule_title']) ?>
        </a>
      <?php endforeach; ?>
      <?php if (count($events) > 3): ?><span class="muted">+<?= count($events) - 3 ?> more</span><?php endif; ?>
    </div>
  <?php endfor; ?>
</div>
