<p class="page-label">USER ROOM &amp; SCHEDULE</p>
<div class="page-head">
  <div>
    <h1>🏫 All Rooms</h1>
    <p class="muted">schedule overview — pick a day, then a room.</p>
  </div>
  <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn btn-primary" href="<?= url('rooms/create') ?>">+ New room</a><?php endif; ?>
</div>

<?php
$keep = ['time' => $time, 'search' => $search, 'map_id' => $map_id ?? '', 'status' => $status, 'organizer' => $organizer];
$qs = fn(array $extra) => url('rooms?' . http_build_query(array_filter(array_merge($keep, $extra), fn($v) => $v !== '' && $v !== null)));
?>
<section class="card week-strip">
  <div class="week-head">
    <a class="week-arrow" href="<?= $qs(['date' => $prev_week]) ?>" aria-label="Previous week">←</a>
    <h2><?= e(date('F Y', strtotime($week_start))) ?></h2>
    <a class="week-arrow" href="<?= $qs(['date' => $next_week]) ?>" aria-label="Next week">→</a>
  </div>
  <div class="week-days">
    <?php foreach ($week as $d): ?>
      <a class="day-cell <?= $d['date'] === $date ? 'day-selected' : '' ?> <?= $d['date'] === date('Y-m-d') ? 'day-today' : '' ?>" href="<?= $qs(['date' => $d['date']]) ?>">
        <span class="day-name"><?= e($d['name']) ?></span>
        <span class="day-num"><?= $d['day'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<form method="get" action="<?= url('rooms') ?>" class="card filter-bar">
  <input type="hidden" name="date" value="<?= e($date) ?>">
  <label>TIME <input type="time" name="time" value="<?= e($time) ?>"></label>
  <label>ROOM <input type="search" name="search" placeholder="Search Room" value="<?= e($search) ?>"></label>
  <label>FLOOR
    <select name="map_id">
      <option value="">All Floors</option>
      <?php foreach ($maps as $m): ?>
        <option value="<?= (int) $m['map_id'] ?>" <?= $map_id === (int) $m['map_id'] ? 'selected' : '' ?>><?= e($m['map_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>STATUS
    <select name="status">
      <option value="">All</option>
      <?php foreach (['available' => 'Available', 'occupied' => 'Confirmed', 'reserved' => 'Reserved', 'unavailable' => 'Unavailable'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>TEACHER/MAYOR <input type="search" name="organizer" placeholder="Search Name…" value="<?= e($organizer) ?>"></label>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn" href="<?= $qs(['recent' => '1']) ?>">↻ Recent Filters</a>
</form>

<p class="date-line">📅 <strong><?= e(date('F j, Y', strtotime($date))) ?></strong> &nbsp;·&nbsp; <?= (int) $total ?> rooms</p>

<?php if (empty($grouped)): ?>
  <p class="muted">No rooms match those filters. <a href="<?= url('rooms') ?>">Clear filters</a></p>
<?php else: ?>
  <?php foreach ($grouped as $g): ?>
    <section class="floor-section">
      <div class="floor-head">
        <h2>🏫 <?= e(ordinal((int) $g['map']['floor_number'])) ?> Floor</h2>
        <span class="floor-count"><?= count($g['cards']) ?> rooms</span>
      </div>
      <div class="room-card-grid">
        <?php foreach ($g['cards'] as $c):
          $r = $c['room'];
          $badge = [
            'available'   => ['badge-green', '● AVAILABLE'],
            'occupied'    => ['badge-blue', '● CONFIRMED'],
            'reserved'    => ['badge-amber', '● RESERVED'],
            'unavailable' => ['badge-grey', '● UNAVAILABLE'],
          ][$c['status']];
        ?>
        <a class="room-card" href="<?= url('rooms/' . (int) $r['room_id']) ?>">
          <div class="room-card-top">
            <strong><?= e($r['room_name']) ?></strong>
            <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
          </div>
          <span class="floor-pill"><?= e(ordinal((int) $r['floor_number'])) ?> floor</span>
          <div class="room-card-meta">
            <span>🕐 <?= $c['booking'] ? e($c['booking']['time']) : '—' ?></span>
            <span>👥 <?= $c['booking'] ? e($c['booking']['organizer']) : '—' ?></span>
          </div>
          <?php if ($c['booking']): ?><p class="muted room-card-title"><?= e($c['booking']['title']) ?></p><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
