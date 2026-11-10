<?php

use App\Models\Room;

$todayByRoom = [];
foreach ($today as $occ) {
    $todayByRoom[(int) $occ['room_id']][] = [
        'title'  => $occ['schedule_title'],
        'start'  => time_12h($occ['start_time']),
        'end'    => time_12h($occ['end_time']),
        'status' => $occ['occurrence_status'],
        'sid'    => (int) $occ['schedule_id'],
    ];
}
$counts = ['total' => count($rooms), 'free' => 0, 'busy' => 0, 'reserved' => 0, 'unavailable' => 0];
foreach ($rooms as $r) {
    if (($r['room_status'] ?? 'active') !== 'active') { $counts['unavailable']++; continue; }
    if (isset($busy[(int) $r['room_id']])) { $counts['busy']++; continue; }
    if (!empty($todayByRoom[(int) $r['room_id']])) { $counts['reserved']++; continue; }
    $counts['free']++;
}
$canBook = \App\Core\Auth::canBook();
?>
<div class="floor-tabs" role="tablist">
  <?php foreach ($maps as $m): ?>
    <a class="floor-tab <?= (int) $m['map_id'] === (int) $map['map_id'] ? 'active' : '' ?>"
       href="<?= url('maps/' . (int) $m['map_id']) ?>"
       role="tab"><?= e($m['map_name']) ?></a>
  <?php endforeach; ?>
</div>

<div class="map-layout">
  <div>
    <div class="map-canvas card" style="margin-bottom:0">
      <span class="map-title">📍 <?= e($map['map_name']) ?> · Floor <?= (int) $map['floor_number'] ?></span>
      <svg viewBox="0 0 <?= (int) $map['map_width'] ?> <?= (int) $map['map_height'] ?>" role="img" aria-label="Floor map of <?= e($map['map_name']) ?>">
        <?php foreach ($rooms as $r):
          $rid = (int) $r['room_id'];
          if (($r['room_status'] ?? 'active') !== 'active') { $status = 'unavailable'; $fill = '#e2e8f0'; $stroke = '#94a3b8'; }
          elseif (isset($busy[$rid])) { $status = 'occupied'; $fill = '#fecaca'; $stroke = '#dc2626'; }
          elseif (!empty($todayByRoom[$rid])) { $status = 'reserved'; $fill = '#fef08a'; $stroke = '#ca8a04'; }
          else { $status = 'available'; $fill = '#bbf7d0'; $stroke = '#16a34a'; }
          [$cx, $cy] = Room::shapeCentre($r);
          $extra = sprintf('class="room-shape room-%s" data-status="%s" data-name="%s" data-desc="%s" stroke="%s" stroke-width="2"',
            $status, $status, e($r['room_name']), e($r['room_description'] ?? ''), $stroke);
          echo Room::shapeToSvg($r, $fill, $extra);
        ?>
          <text class="room-label" x="<?= $cx ?>" y="<?= $cy - 2 ?>" text-anchor="middle"><?= e($r['room_name']) ?></text>
          <text class="room-sub" x="<?= $cx ?>" y="<?= $cy + 14 ?>" text-anchor="middle"><?= $status === 'occupied' ? 'Occupied now' : ucfirst($status) ?></text>
        <?php endforeach; ?>
      </svg>
    </div>
    <div class="map-stats">
      <div class="map-stat"><strong><?= (int) $counts['total'] ?></strong><span>Total rooms</span></div>
      <div class="map-stat"><strong><?= (int) $counts['busy'] ?></strong><span>Occupied now</span></div>
      <div class="map-stat"><strong><?= (int) $counts['free'] ?></strong><span>Available now</span></div>
      <div class="map-stat"><strong><?= (int) $counts['reserved'] ?></strong><span>Reserved today</span></div>
    </div>
  </div>

  <aside>
    <div class="card room-panel" id="room-panel">
      <h2 id="panel-title">Select a room</h2>
      <p class="muted" id="panel-desc">Click any room on the map to see what's happening there today.</p>
      <div id="panel-body"></div>
      <div class="panel-actions" id="panel-actions"></div>
    </div>
    <div class="card">
      <h3>Legend</h3>
      <div class="map-legend">
        <span class="legend-item"><span class="dot dot-free"></span> Available</span>
        <span class="legend-item"><span class="dot dot-busy"></span> Occupied now</span>
        <span class="legend-item"><span class="dot dot-reserved"></span> Reserved later today</span>
        <span class="legend-item"><span class="dot dot-unavailable"></span> Unavailable</span>
      </div>
      <h3 style="margin-top:1rem">Quick search</h3>
      <input class="search-box" type="search" id="room-search" placeholder="Search rooms… (e.g. 101)">
    </div>
    <?php if (!empty($show_editor_link) && \App\Core\Auth::isAdmin()): ?>
    <div class="card">
      <h3>Admin</h3>
      <p class="muted">Move rooms, resize them and see the map redesign live.</p>
      <a class="btn btn-primary" href="<?= url('maps/' . (int) $map['map_id'] . '/editor') ?>">✏️ Edit this map →</a>
    </div>
    <?php endif; ?>
  </aside>
</div>
<?php


$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<script type="application/json" id="room-bookings"><?= json_encode($todayByRoom, $jsonFlags) ?></script>
<script type="application/json" id="room-meta"><?= json_encode([
  'canBook' => $canBook,
  'bookUrl' => url('schedules/create'),
  'roomUrl' => url('rooms/'),
  'mapId'   => (int) $map['map_id'],
], $jsonFlags) ?></script>
