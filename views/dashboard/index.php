<div class="stat-grid">
  <div class="stat-card tint-purple"><div class="stat-icon"></div><div><strong><?= (int) $stats['rooms'] ?></strong><span>Rooms</span></div></div>
  <div class="stat-card tint-green"><div class="stat-icon"></div><div><strong><?= (int) $stats['free_now'] ?></strong><span>Free right now</span></div></div>
  <div class="stat-card tint-red"><div class="stat-icon"></div><div><strong><?= (int) $stats['busy_now'] ?></strong><span>Occupied now</span></div></div>
  <div class="stat-card tint-amber"><div class="stat-icon"></div><div><strong><?= (int) $stats['today'] ?></strong><span>Bookings today</span></div></div>
</div>

<?php if (!empty($first_map)): ?>
<?php
  $map = $first_map; $rooms = $first_rooms; $show_editor_link = true; $hide_map_stats = true;
  $tab_url_prefix = url('') . '?map=';
  require __DIR__ . '/../maps/_interactive_map.php';
?>
<?php else: ?>
<section class="card"><p class="muted">No floor maps yet.<?php if (\App\Core\Auth::isAdmin()): ?> <a href="<?= url('maps/create') ?>">Create the first map</a>.<?php endif; ?></p></section>
<?php endif; ?>
