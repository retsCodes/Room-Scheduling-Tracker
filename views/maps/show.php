<div class="page-head">
  <div>
    <h1>🗺️ <?= e($map['map_name']) ?></h1>
    <p class="muted">Floor <?= (int) $map['floor_number'] ?> · <?= status_badge($map['map_status']) ?> · Live status for <?= e($today_date) ?>.</p>
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
