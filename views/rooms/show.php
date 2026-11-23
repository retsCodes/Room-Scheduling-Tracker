<?php
$now = date('H:i:s');
$nowStatus = 'available';
$currentOcc = null; $nextOcc = null;
foreach ($today_occ as $o) {
    if ($o['occurrence_status'] === 'scheduled' && $o['start_time'] <= $now && $o['end_time'] > $now) { $nowStatus = 'occupied'; $currentOcc = $o; }
    elseif ($o['occurrence_status'] === 'scheduled' && $o['start_time'] > $now && $nextOcc === null) { if ($nowStatus === 'available') $nowStatus = 'reserved'; $nextOcc = $o; }
}
if (($room['room_status'] ?? 'active') !== 'active') $nowStatus = 'unavailable';
$heroBadge = [
  'available'   => ['badge-green', '● AVAILABLE'],
  'occupied'    => ['badge-blue', '● CONFIRMED'],
  'reserved'    => ['badge-amber', '● RESERVED'],
  'unavailable' => ['badge-grey', '● UNAVAILABLE'],
][$nowStatus];
$barClass = function ($o) {
    if ($o['occurrence_status'] === 'cancelled') return 'tl-red';
    if ($o['occurrence_status'] === 'completed') return 'tl-green';
    return (int) ($o['admin_confirmed'] ?? 0) === 1 ? 'tl-blue' : 'tl-amber';
};
?>
<a class="muted" href="<?= url('rooms') ?>">← ALL ROOMS</a>
<div class="page-head">
  <div>
    <h1>Room Details</h1>
    <p class="muted">📅 <?= e(date('F j, Y', strtotime($today))) ?> · Today</p>
  </div>
  <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
    <span class="muted"><?= e($room['room_name']) ?> · <?= e(ordinal((int) $room['floor_number'])) ?> Floor</span>
    <?php if (\App\Core\Auth::canBook()): ?><a class="btn btn-primary" href="<?= url('schedules/create?room_id=' . (int) $room['room_id']) ?>">+ Add Reserve</a><?php endif; ?>
    <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn" href="<?= url('rooms/' . (int) $room['room_id'] . '/edit') ?>">Edit room</a><?php endif; ?>
  </div>
</div>

<div class="rd-grid">
  <section class="card rd-left">
    <?php if (!empty($room['room_image'])): ?>
      <img class="room-photo" src="<?= e(img_url($room['room_image'], 'uploads/rooms')) ?>" alt="Photo of <?= e($room['room_name']) ?>">
    <?php elseif (\App\Core\Auth::isAdmin()): ?>
      <div class="photo-placeholder">
        <p class="muted" style="margin:0">🖼️ No photo yet</p>
        <p style="margin:.4rem 0 0"><a href="<?= url('rooms/' . (int) $room['room_id'] . '/edit') ?>">Add a photo of this room →</a></p>
      </div>
    <?php endif; ?>
    <div class="rd-title-row">
      <h2><?= e($room['room_name']) ?></h2>
      <span class="badge <?= $heroBadge[0] ?> room-big-status"><?= $heroBadge[1] ?></span>
    </div>
    <span class="floor-pill"><?= e(strtoupper(ordinal((int) $room['floor_number']))) ?> FLOOR</span>
    <div class="rd-current">
      <div>
        <span class="rd-label">CURRENT SCHEDULE</span>
        <strong><?= $currentOcc ? time_12h($currentOcc['start_time']) . ' - ' . time_12h($currentOcc['end_time']) : ($nextOcc ? 'Next: ' . time_12h($nextOcc['start_time']) : 'Free all day') ?></strong>
      </div>
      <div>
        <span class="rd-label">INSTRUCTOR</span>
        <strong><?= $currentOcc ? e($currentOcc['organizer_name'] ?? '') : ($nextOcc ? e($nextOcc['organizer_name'] ?? '') : '-') ?></strong>
      </div>
    </div>
    <h3 class="rd-section">ALL SCHEDULES</h3>
    <?php if (empty($today_occ)): ?>
      <p class="muted">No bookings today.</p>
    <?php else: ?>
      <?php foreach ($today_occ as $occ): ?>
      <div class="rd-schedule-row">
        <span><?= time_12h($occ['start_time']) ?> - <?= time_12h($occ['end_time']) ?></span>
        <span><strong><?= e($occ['schedule_title']) ?></strong><br><span class="muted"><?= e($occ['organizer_name'] ?? '') ?></span></span>
        <span class="rd-bar <?= $barClass($occ) ?>"></span>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($room['room_description'])): ?><p class="muted" style="margin-top:1rem"><?= e($room['room_description']) ?></p><?php endif; ?>
    <div class="panel-actions">
      <a class="btn" href="<?= url('maps/' . (int) $room['map_id']) ?>">View floor map →</a>
      <?php if (\App\Core\Auth::isAdmin()): ?><span class="muted">Shape: <?= e($room['shape_type']) ?></span><?php endif; ?>
    </div>
    <?php if (!\App\Core\Auth::canBook()): ?>
      <p class="muted">Students can view schedules here - booking is for teachers, mayors and org officers.</p>
    <?php endif; ?>
  </section>

  <section class="card rd-right">
    <h2>Timeline View</h2>
    <?php
    $toMin = fn($t) => (int) substr($t, 0, 2) * 60 + (int) substr($t, 3, 2);
    $tlBookings = array_map(fn($o) => [
        'start' => $toMin($o['start_time']),
        'end' => $toMin($o['end_time']),
        'title' => $o['schedule_title'],
        'status' => $o['occurrence_status'],
        'confirmed' => (int) ($o['admin_confirmed'] ?? 0),
    ], $today_occ);
    $tlStart = 420; $tlEnd = 1140;
    if (!empty($tlBookings)) {
        $tlStart = min($tlStart, min(array_column($tlBookings, 'start')));
        $tlEnd = max($tlEnd, max(array_column($tlBookings, 'end')));
    }
    $tlData = ['start' => $tlStart, 'end' => $tlEnd, 'now' => (int) date('H') * 60 + (int) date('i'), 'bookings' => $tlBookings];
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    ?>
    <?php if (empty($today_occ)): ?>
      <p class="muted">Nothing booked today - this room is free all day.</p>
    <?php endif; ?>
    <div class="tl-controls">
      <span class="tl-btns">
        <button type="button" class="btn btn-small" data-zoom="in" aria-label="Zoom in">+</button>
        <button type="button" class="btn btn-small" data-zoom="out" aria-label="Zoom out">−</button>
        <button type="button" class="btn btn-small" data-zoom="reset">Reset</button>
      </span>
      <span class="tl-presets">
        <button type="button" class="btn btn-small" data-preset="all">All day</button>
        <button type="button" class="btn btn-small" data-preset="morning">Morning 7-12</button>
        <button type="button" class="btn btn-small" data-preset="afternoon">Afternoon 12-17</button>
        <button type="button" class="btn btn-small" data-preset="evening">Evening 17-22</button>
      </span>
    </div>
    <p class="muted tl-range" id="tl-range"></p>
    <div class="timeline" id="timeline">
      <div class="tl-grid" id="tl-grid"></div>
      <div class="tl-now" id="tl-now" hidden></div>
      <div id="tl-lanes"></div>
    </div>
    <div class="timeline-scale" id="tl-scale"></div>
    <p class="muted tl-legend" style="margin-top:.6rem">
      <span><span class="dot dot-confirmed"></span> confirmed</span>
      <span><span class="dot dot-reserved"></span> reserved</span>
      <span><span class="dot dot-busy"></span> cancelled</span>
      <span><span class="dot dot-free"></span> completed</span>
      <span class="muted">Drag across the timeline to zoom. A gold outline means an admin confirmed it.</span>
    </p>
    <script type="application/json" id="timeline-data"><?= json_encode($tlData, $jsonFlags) ?></script>
  </section>
</div>

<h2 style="margin-top:1.4rem">Upcoming bookings (next 30 days)</h2>
<?php if (empty($upcoming)): ?><p class="muted">Nothing booked in the next 30 days.</p>
<?php else: ?>
  <div class="booking-list">
    <?php foreach ($upcoming as $b): ?>
      <a class="booking-card" href="<?= url('schedules/' . (int) $b['schedule_id']) ?>">
        <div class="booking-card-date">
          <span class="booking-card-day"><?= (int) date('j', strtotime($b['occurrence_date'])) ?></span>
          <span class="booking-card-mon"><?= date('M', strtotime($b['occurrence_date'])) ?></span>
        </div>
        <div class="booking-card-body">
          <span class="booking-card-time"><?= time_12h($b['start_time']) ?>–<?= time_12h($b['end_time']) ?></span>
          <span class="booking-card-title"><?= e($b['schedule_title']) ?></span>
          <?= confirm_badge($b['admin_confirmed'] ?? 0) ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
