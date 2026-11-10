<h1><?= $room ? 'Edit room' : 'New room' ?></h1>
<form method="post" action="<?= $room ? url('rooms/' . (int) $room['room_id']) : url('rooms') ?>" class="card form">
  <?= csrf_field() ?>
  <label>Floor map
    <select name="map_id" required>
      <?php foreach ($maps as $m): ?>
        <option value="<?= (int) $m['map_id'] ?>" <?= (int) ($room['map_id'] ?? $prefill_map ?? 0) === (int) $m['map_id'] ? 'selected' : '' ?>><?= e($m['map_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Room name
    <input type="text" name="room_name" required value="<?= e($room['room_name'] ?? '') ?>" placeholder="Room 101">
  </label>
  <label>Shape type
    <select name="shape_type" id="shape_type">
      <option value="rectangle" <?= ($room['shape_type'] ?? 'rectangle') === 'rectangle' ? 'selected' : '' ?>>Rectangle</option>
      <option value="polygon" <?= ($room['shape_type'] ?? '') === 'polygon' ? 'selected' : '' ?>>Polygon</option>
      <option value="path" <?= ($room['shape_type'] ?? '') === 'path' ? 'selected' : '' ?>>Path</option>
    </select>
  </label>
  <div class="grid-2" id="rect_fields">
    <label>X <input type="number" name="shape_x" value="20"></label>
    <label>Y <input type="number" name="shape_y" value="20"></label>
    <label>Width <input type="number" name="shape_w" value="260"></label>
    <label>Height <input type="number" name="shape_h" value="180"></label>
  </div>
  <label>Shape data (JSON) — leave blank to use the X/Y/W/H above; or paste JSON
    <textarea name="shape_data" rows="3" placeholder='{"type":"polygon","points":[{"x":20,"y":20},{"x":180,"y":20},{"x":180,"y":70}]}'><?= e($room['shape_data'] ?? '') ?></textarea>
  </label>
  <p class="muted">Examples — rectangle: <code>{"type":"rectangle","x":20,"y":20,"width":260,"height":180}</code><br>
  Polygon: <code>{"type":"polygon","points":[{"x":20,"y":20},{"x":180,"y":20},{"x":180,"y":70}]}</code><br>
  Path: <code>{"type":"path","d":"M20,20 L180,20 L180,100 Q100,140 20,100 Z"}</code></p>
  <label>Description (optional)
    <input type="text" name="room_description" value="<?= e($room['room_description'] ?? '') ?>" placeholder="Computer laboratory">
  </label>
  <label>Status
    <select name="room_status">
      <option value="active" <?= ($room['room_status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="unavailable" <?= ($room['room_status'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
    </select>
  </label>
  <button class="btn btn-primary" type="submit"><?= $room ? 'Save changes' : 'Create room' ?></button>
  <a class="btn" href="<?= url('rooms') ?>">Cancel</a>
</form>
<?php if ($room && \App\Core\Auth::isAdmin()): ?>
<form method="post" action="<?= url('rooms/' . (int) $room['room_id'] . '/delete') ?>" class="card form danger-zone" data-confirm="Delete this room? Schedules using it must be cancelled first.">
  <?= csrf_field() ?>
  <button class="btn btn-danger" type="submit">Delete room</button>
</form>
<?php endif; ?>
