<div class="page-head">
  <h1>Floor maps</h1>
  <?php if (\App\Core\Auth::isAdmin()): ?><a class="btn btn-primary" href="<?= url('maps/create') ?>">+ New map</a><?php endif; ?>
</div>
<?php if (empty($maps)): ?>
  <p class="muted">No maps yet. An admin can add the first floor map.</p>
<?php else: ?>
<table class="table card">
  <thead><tr><th>Map</th><th>Floor</th><th>Size</th><th>Rooms</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($maps as $m): ?>
    <tr>
      <td><a href="<?= url('maps/' . (int) $m['map_id']) ?>"><?= e($m['map_name']) ?></a></td>
      <td><?= (int) $m['floor_number'] ?></td>
      <td><?= e((string) $m['map_width']) ?> × <?= e((string) $m['map_height']) ?></td>
      <td><?= (int) $m['room_count'] ?></td>
      <td><?= status_badge($m['map_status']) ?></td>
      <td>
        <?php if (\App\Core\Auth::isAdmin()): ?>
          <a href="<?= url('maps/' . (int) $m['map_id'] . '/edit') ?>">Edit</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
