<?php
$nowStatus = 'available';
foreach ($today_occ as $o) {
    if ($o['occurrence_status'] === 'scheduled' && $o['start_time'] <= date('H:i:s') && $o['end_time'] > date('H:i:s')) { $nowStatus = 'occupied'; break; }
    if ($o['occurrence_status'] === 'scheduled' && $o['start_time'] > date('H:i:s')) $nowStatus = 'reserved';
}
if (($room['room_status'] ?? 'active') !== 'active') $nowStatus = 'unavailable';
$heroBadge = [
  'available'   => ['badge-green', '● AVAILABLE'],
  'occupied'    => ['badge-blue', '● CONFIRMED'],
  'reserved'    => ['badge-amber', '● RESERVED'],
  'unavailable' => ['badge-grey', '● UNAVAILABLE'],
][$nowStatus];
?>
<p class="page-label">ROOM DETAILS</p>
<div class="page-head">
  <div class="room-hero">
    <h1><?= e($room['room_name']) ?></h1>
    <span class="floor-pill"><?= e(ordinal((int) $room['floor_number'])) ?> floor</span>
    <span class="badge <?= $heroBadge[0] ?> room-big-status"><?= $heroBadge[1] ?></span>
  </div>
  <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn" href="<?= url('rooms/' . (int) $room['room_id'] . '/edit') ?>">Edit room</a><?php endif; ?>
</div>

<div class="grid-2">
  <section class="card">
    <h2>About this room</h2>
    <p><strong>Floor map:</strong> <?= e($room['map_name']) ?></p>
    <p><strong>Shape:</strong> <?= e($room['shape_type']) ?></p>
    <p><strong>Status:</strong> <?= status_badge($room['room_status']) ?></p>
    <?php if (!empty($room['room_description'])): ?><p><?= e($room['room_description']) ?></p><?php endif; ?>
    <div class="panel-actions">
      <?php if (\App\Core\Auth::canBook()): ?><a class="btn btn-primary" href="<?= url('schedules/create?room_id=' . (int) $room['room_id']) ?>">＋ Add Reserve</a><?php endif; ?>
      <a class="btn" href="<?= url('maps/' . (int) $room['map_id']) ?>">View floor map →</a>
    </div>
    <?php if (!\App\Core\Auth::canBook()): ?>
      <p class="muted">Only teachers, class mayors and org officers can reserve rooms. Students can view schedules here.</p>
    <?php endif; ?>
  </section>

  <section class="card">
    <h2>⏱️ Timeline - today, <?= e(date('l, F j', strtotime($today))) ?></h2>
    <?php
    $toMin = fn(string $t): int => (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2);
    $tlStart = 7 * 60; $tlEnd = 19 * 60;
    foreach ($today_occ as $o) {
        $tlStart = min($tlStart, (int) floor($toMin($o['start_time']) / 60) * 60);
        $tlEnd = max($tlEnd, (int) ceil($toMin($o['end_time']) / 60) * 60);
    }
    if ($tlEnd - $tlStart < 240) $tlEnd = $tlStart + 240;
    $tlSpan = $tlEnd - $tlStart;
    $tickStep = $tlSpan > 720 ? 120 : 60;
    $ticks = [];
    for ($m = $tlStart; $m <= $tlEnd; $m += $tickStep) {
        $ticks[] = ['left' => ($m - $tlStart) / $tlSpan * 100, 'label' => date('g A', mktime((int) ($m / 60), $m % 60))];
    }
    if (empty($ticks) || end($ticks)['left'] < 100) {
        $ticks[] = ['left' => 100, 'label' => date('g A', mktime((int) ($tlEnd / 60), $tlEnd % 60))];
    }
    $tlClass = fn(string $s): string => $s === 'cancelled' ? 'tl-red' : ($s === 'completed' ? 'tl-green' : 'tl-blue');
    ?>
    <?php if (empty($today_occ)): ?>
      <p class="muted">Nothing booked today - this room is free all day.</p>
    <?php else: ?>
      <div class="timeline">
        <?php foreach ($today_occ as $occ):
          $left = max(0, ($toMin($occ['start_time']) - $tlStart) / $tlSpan * 100);
          $width = max(2, min(($toMin($occ['end_time']) - $toMin($occ['start_time'])) / $tlSpan * 100, 100 - $left));
        ?>
        <div class="timeline-row">
          <span class="tl-time"><?= time_12h($occ['start_time']) ?></span>
          <div class="timeline-track">
            <a class="timeline-block <?= $tlClass($occ['occurrence_status']) ?>" style="left:<?= round($left, 1) ?>%;width:<?= round($width, 1) ?>%"
               href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>" title="<?= e($occ['schedule_title']) ?> <?= time_12h($occ['start_time']) ?>–<?= time_12h($occ['end_time']) ?>"><?= e($occ['schedule_title']) ?></a>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="timeline-scale-row">
          <span></span>
          <div class="timeline-scale">
            <?php foreach ($ticks as $t): ?>
              <span class="timeline-tick" style="left:<?= round($t['left'], 2) ?>%"><?= e($t['label']) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <p class="muted" style="margin-top:.6rem"><span class="badge badge-blue">Booked</span> <span class="badge badge-red">Cancelled</span> <span class="badge badge-green">Completed</span></p>
    <?php endif; ?>
  </section>
</div>

<h2>Today's bookings</h2>
<?php if (empty($today_occ)): ?><p class="muted">No bookings today.</p>
<?php else: ?>
  <?php foreach ($today_occ as $occ): ?>
  <div class="booking-card">
    <span class="bk-time">🕐 <?= time_12h($occ['start_time']) ?> – <?= time_12h($occ['end_time']) ?></span>
    <a class="bk-title" href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>"><?= e($occ['schedule_title']) ?></a>
    <span>👥 <?= e($occ['organizer_name'] ?? '') ?></span>
    <?= confirm_badge($occ['admin_confirmed'] ?? 0) ?>
    <?= status_badge($occ['occurrence_status']) ?>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<h2>Upcoming bookings (next 30 days)</h2>
<?php if (empty($upcoming)): ?><p class="muted">No upcoming bookings for this room.</p>
<?php else: ?>
  <?php foreach ($upcoming as $occ): ?>
  <div class="booking-card">
    <span class="bk-time"><?= e(date('D, M j', strtotime($occ['occurrence_date']))) ?> · <?= time_12h($occ['start_time']) ?> – <?= time_12h($occ['end_time']) ?></span>
    <a class="bk-title" href="<?= url('schedules/' . (int) $occ['schedule_id']) ?>"><?= e($occ['schedule_title']) ?></a>
    <span>👥 <?= e($occ['organizer_name'] ?? '') ?></span>
    <?= confirm_badge($occ['admin_confirmed'] ?? 0) ?>
    <?= status_badge($occ['occurrence_status']) ?>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
