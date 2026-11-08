<div class="page-head">
  <h1>Rooms</h1>
  <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn btn-primary" href="<?= url('rooms/create') ?>">+ New room</a><?php endif; ?>
</div>
<form method="get" action="<?= url('rooms') ?>" class="card form-inline">
  <label>Floor map
    <select name="map_id" onchange="this.form.submit()">
      <option value="">All maps</option>
      <?php foreach ($maps as $m): ?>
        <option value="<?= (int) $m['map_id'] ?>" <?= $map_id === (int) $m['map_id'] ? 'selected' : '' ?>><?= e($m['map_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</form>
<?php if (empty($rooms)): ?>
  <p class="muted">No rooms yet.</p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Room</th><th>Map</th><th>Shape</th><th>Description</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($rooms as $r): ?>
    <tr>
      <td><a href="<?= url('rooms/' . (int) $r['room_id']) ?>"><?= e($r['room_name']) ?></a></td>
      <td><?= e($r['map_name']) ?> (Floor <?= (int) $r['floor_number'] ?>)</td>
      <td><?= e($r['shape_type']) ?></td>
      <td><?= e($r['room_description'] ?? '') ?></td>
      <td><?= status_badge($r['room_status']) ?></td>
      <td><?php if (\App\Core\Auth::isAdmin()): ?><a href="<?= url('rooms/' . (int) $r['room_id'] . '/edit') ?>">Edit</a><?php endif; ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
