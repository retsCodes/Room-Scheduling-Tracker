<div class="page-head">
  <div>
    <h1>🗺️ <?= e($map['map_name']) ?></h1>
    <p class="muted">Floor <?= (int) $map['floor_number'] ?> · <?= e((string) $map['map_width']) ?> × <?= e((string) $map['map_height']) ?> · <?= status_badge($map['map_status']) ?> · Colours show live status for <?= e($today_date) ?>.</p>
  </div>
  <div>
    <?php if (\App\Core\Auth::isAdmin()): ?>
      <a class="btn" href="<?= url('maps/' . (int) $map['map_id'] . '/editor') ?>">✏️ Edit map layout</a>
      <a class="btn" href="<?= url('rooms/create?map_id=' . (int) $map['map_id']) ?>">+ Add room</a>
      <a class="btn" href="<?= url('maps/' . (int) $map['map_id'] . '/edit') ?>">Edit map</a>
    <?php endif; ?>
  </div>
</div>

<?php $show_editor_link = true; require __DIR__ . '/_interactive_map.php'; ?>

<h2 style="margin-top:1.2rem">Rooms on this map</h2>
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
