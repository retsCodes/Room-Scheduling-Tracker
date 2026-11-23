<div class="page-head">
  <div><h1><?= $map ? 'Edit map' : 'New floor map' ?></h1></div>
  <?php if ($map): ?>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a class="btn" href="<?= url('maps/' . (int) $map['map_id'] . '/editor') ?>">Open map editor →</a>
    <a class="btn btn-primary" href="<?= url('maps/create') ?>">+ New map</a>
  </div>
  <?php endif; ?>
</div>
<?php if ($map): ?>
<p class="muted">Map info: <?= e($map['map_name']) ?> · Floor <?= (int) $map['floor_number'] ?> · <?= e((string) $map['map_width']) ?> × <?= e((string) $map['map_height']) ?> · <?= status_badge($map['map_status']) ?></p>
<?php endif; ?>
<form method="post" action="<?= $map ? url('maps/' . (int) $map['map_id']) : url('maps') ?>" class="card form">
  <?= csrf_field() ?>
  <label>Map name
    <input type="text" name="map_name" required value="<?= e($map['map_name'] ?? '') ?>" placeholder="Main Building - Floor 1">
  </label>
  <label>Floor number
    <input type="number" name="floor_number" required value="<?= e((string) ($map['floor_number'] ?? 1)) ?>">
  </label>
  <div class="grid-2">
    <label>Map width
      <input type="number" step="0.01" name="map_width" required value="<?= e((string) ($map['map_width'] ?? '1200.00')) ?>">
    </label>
    <label>Map height
      <input type="number" step="0.01" name="map_height" required value="<?= e((string) ($map['map_height'] ?? '800.00')) ?>">
    </label>
  </div>
  <label>Background image path (optional)
    <input type="text" name="background_image" value="<?= e($map['background_image'] ?? '') ?>" placeholder="maps/floor1.png">
  </label>
  <label>Status
    <select name="map_status">
      <option value="active" <?= ($map['map_status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="archived" <?= ($map['map_status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
    </select>
  </label>
  <button class="btn btn-primary" type="submit"><?= $map ? 'Save changes' : 'Create map' ?></button>
  <a class="btn" href="<?= $map ? url('maps/' . (int) $map['map_id']) : url('maps') ?>">Cancel</a>
</form>
<?php if ($map && \App\Core\Auth::isAdmin()): ?>
<form method="post" action="<?= url('maps/' . (int) $map['map_id'] . '/delete') ?>" class="card form danger-zone" data-confirm="Delete this map and all its rooms? This cannot be undone.">
  <?= csrf_field() ?>
  <button class="btn btn-danger" type="submit">Delete map</button>
</form>
<?php endif; ?>
