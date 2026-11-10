<?php
use App\Models\Room;

$editorRooms = [];
foreach ($rooms as $r) {
    $editorRooms[] = [
        'room_id' => (int) $r['room_id'],
        'room_name' => $r['room_name'],
        'shape_type' => $r['shape_type'],
        'shape_data' => json_decode((string) ($r['shape_data'] ?? ''), true) ?: [],
        'room_description' => $r['room_description'] ?? '',
        'room_status' => $r['room_status'],
    ];
}
?>
<div class="page-head">
  <div>
    <h1><?= e($map['map_name']) ?></h1>
    <p class="muted">Map editor — drag rooms to move them, drag the white corner dot to resize.</p>
  </div>
  <div>
    <a class="btn" href="<?= url('maps/' . (int) $map['map_id'] . '/export') ?>">EXPORT</a>
    <form method="post" action="<?= url('maps/' . (int) $map['map_id'] . '/import') ?>" enctype="multipart/form-data" class="inline-form">
      <?= csrf_field() ?>
      <label class="btn" for="import-file" style="cursor:pointer">IMPORT</label>
      <input type="file" id="import-file" name="import_file" accept=".json,application/json" style="display:none" onchange="this.form.submit()">
    </form>
  </div>
</div>

<div class="editor-layout">
  <div>
    <div class="map-canvas card editor-wrap" id="editor-map-wrap" style="margin-bottom:0">
      <div class="editor-tools">
        <a class="tool-btn tool-back" href="<?= url('maps/' . (int) $map['map_id']) ?>" title="Back to map">←</a>
        <button class="tool-btn active" type="button" id="tool-select" title="Select / move rooms">▢</button>
        <button class="tool-btn tool-add" type="button" id="tool-add" title="Add room — then click the map">▣</button>
        <button class="tool-btn" type="button" id="zoom-in" title="Zoom in">＋</button>
        <button class="tool-btn" type="button" id="zoom-out" title="Zoom out">－</button>
        <button class="tool-btn" type="button" id="fullscreen-btn" title="Fullscreen">⛶</button>
      </div>
      <svg id="editor-svg" viewBox="0 0 <?= (int) $map['map_width'] ?> <?= (int) $map['map_height'] ?>" role="img" aria-label="Editable floor map">
        <?php foreach ($rooms as $r):
          $rid = (int) $r['room_id'];
          if (($r['room_status'] ?? 'active') !== 'active') { $fill = '#e2e8f0'; $stroke = '#94a3b8'; }
          elseif (isset($busy[$rid])) { $fill = '#fecaca'; $stroke = '#dc2626'; }
          else { $fill = '#bbf7d0'; $stroke = '#16a34a'; }
          [$cx, $cy] = Room::shapeCentre($r);
          $shape = json_decode((string) ($r['shape_data'] ?? ''), true) ?: [];
        ?>
        <g class="editor-room" data-room-id="<?= $rid ?>">
          <?= Room::shapeToSvg($r, $fill, 'stroke="' . $stroke . '" stroke-width="2"') ?>
          <text class="room-label" x="<?= $cx ?>" y="<?= $cy - 2 ?>" text-anchor="middle"><?= e($r['room_name']) ?></text>
          <text class="room-sub" x="<?= $cx ?>" y="<?= $cy + 14 ?>" text-anchor="middle"><?= e($r['room_status']) ?></text>
          <?php if (($r['shape_type'] ?? 'rectangle') === 'rectangle'): ?>
          <circle class="editor-handle" data-room-id="<?= $rid ?>" cx="<?= (float) ($shape['x'] ?? 0) + (float) ($shape['width'] ?? 100) ?>" cy="<?= (float) ($shape['y'] ?? 0) + (float) ($shape['height'] ?? 80) ?>" r="9" />
          <?php endif; ?>
        </g>
        <?php endforeach; ?>
      </svg>
    </div>
    <div class="editor-legend">
      <span><span class="dot dot-free"></span> available</span>
      <span><span class="dot dot-busy"></span> occupied</span>
      <span><span class="dot dot-reserved"></span> reserved</span>
      <span><span class="dot dot-unavailable"></span> unavailable</span>
    </div>
    <p class="editor-hint" id="editor-status">All changes saved.</p>
  </div>

  <aside class="card props-panel" id="props-panel">
    <h2>Room Properties</h2>
    <p class="muted" id="props-empty">Click a room on the map to edit it.</p>
    <div id="props-form" style="display:none">
      <label>Room Name <input type="text" id="prop-name" maxlength="100"></label>
      <div class="props-row">
        <label>width (px) <input type="number" id="prop-w" step="10" min="40"></label>
        <label>Height (px) <input type="number" id="prop-h" step="10" min="40"></label>
      </div>
      <div class="props-row">
        <label>X position (px) <input type="number" id="prop-x" step="10" min="0"></label>
        <label>Y position (px) <input type="number" id="prop-y" step="10" min="0"></label>
      </div>
      <p class="editor-hint" id="prop-shape-note" style="display:none">Only rectangles can be resized here — use the room form for exact points.</p>
      <label>Status
        <select id="prop-status">
          <option value="active">● Available</option>
          <option value="unavailable">● Unavailable</option>
        </select>
      </label>
      <label>Description (Optional) <textarea id="prop-desc" rows="2"></textarea></label>
      <label>Shape
        <select id="prop-shape">
          <option value="rectangle">Rectangle</option>
          <option value="polygon">Polygon</option>
          <option value="path">Path</option>
        </select>
      </label>
      <button class="btn btn-primary" type="button" id="save-layout" style="width:100%;padding:.7rem"><span id="unsaved-dot" class="unsaved-dot" style="display:none"></span>Save Changes</button>
      <button class="btn" type="button" id="discard-layout" style="width:100%;margin-top:.5rem">Discard changes</button>
      <p style="text-align:center;margin-top:.6rem"><button class="btn btn-small btn-danger" type="button" id="delete-room">Delete room</button></p>
    </div>
  </aside>
</div>

<?php $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT; ?>
<script type="application/json" id="editor-rooms"><?= json_encode($editorRooms, $jsonFlags) ?></script>
<script type="application/json" id="editor-meta"><?= json_encode([
  'mapId' => (int) $map['map_id'],
  'mapWidth' => (int) $map['map_width'],
  'mapHeight' => (int) $map['map_height'],
  'saveUrl' => url('maps/' . (int) $map['map_id'] . '/layout'),
  'csrf' => csrf_token(),
], $jsonFlags) ?></script>
