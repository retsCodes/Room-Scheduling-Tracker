<div class="page-head">
  <h1><?= e($map['map_name']) ?></h1>
  <div>
    <?php if (\App\Core\Auth::isAdmin()): ?>
      <a class="btn" href="<?= url('rooms/create?map_id=' . (int) $map['map_id']) ?>">+ Add room</a>
      <a class="btn" href="<?= url('maps/' . (int) $map['map_id'] . '/edit') ?>">Edit map</a>
    <?php endif; ?>
  </div>
</div>
<p class="muted">Floor <?= (int) $map['floor_number'] ?> · <?= e((string) $map['map_width']) ?> × <?= e((string) $map['map_height']) ?> · <?= status_badge($map['map_status']) ?> · Colours show live status for <?= e($today) ?> right now.</p>

<div class="card map-wrap">
  <svg viewBox="0 0 <?= e((string) $map['map_width']) ?> <?= e((string) $map['map_height']) ?>" class="floor-map" role="img" aria-label="Floor map">
    <?php if (!empty($map['background_image'])): ?>
      <image href="<?= e($map['background_image']) ?>" x="0" y="0" width="<?= e((string) $map['map_width']) ?>" height="<?= e((string) $map['map_height']) ?>" />
    <?php endif; ?>
    <?php foreach ($rooms as $room):
      $isBusy = isset($busy[(int) $room['room_id']]);
      $isUnavailable = $room['room_status'] !== 'active';
      $fill = $isUnavailable ? '#e5e7eb' : ($isBusy ? '#fca5a5' : '#86efac');
      [$cx, $cy] = \App\Models\Room::shapeCentre($room);
    ?>
      <a href="<?= url('rooms/' . (int) $room['room_id']) ?>">
        <?= \App\Models\Room::shapeToSvg($room, $fill, 'stroke="#1f2937" stroke-width="2" class="room-shape"') ?>
        <text x="<?= $cx ?>" y="<?= $cy ?>" text-anchor="middle" class="room-label"><?= e($room['room_name']) ?></text>
        <text x="<?= $cx ?>" y="<?= $cy + 18 ?>" text-anchor="middle" class="room-sub"><?= $isUnavailable ? 'Unavailable' : ($isBusy ? 'Busy now' : 'Free now') ?></text>
      </a>
    <?php endforeach; ?>
  </svg>
</div>
<p class="legend"><span class="dot dot-free"></span> Free now &nbsp; <span class="dot dot-busy"></span> Busy now &nbsp; <span class="dot dot-gray"></span> Unavailable</p>

<h2>Rooms on this map</h2>
<?php if (empty($rooms)): ?><p class="muted">No rooms on this map yet.</p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Room</th><th>Shape</th><th>Description</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($rooms as $room): ?>
    <tr>
      <td><a href="<?= url('rooms/' . (int) $room['room_id']) ?>"><?= e($room['room_name']) ?></a></td>
      <td><?= e($room['shape_type']) ?></td>
      <td><?= e($room['room_description'] ?? '') ?></td>
      <td><?= status_badge($room['room_status']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
