<?php
use App\Models\Room;
/** Admin map editor: drag rooms, resize rectangles, edit properties live. */
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
    <h1>✏️ Edit map layout — <?= e($map['map_name']) ?></h1>
    <p class="muted">Drag rooms to move them · drag the white corner dot to resize rectangles · edit properties on the right and watch the map redesign live.</p>
  </div>
  <div>
    <a class="btn" href="<?= url('maps/' . (int) $map['map_id']) ?>">← Back to map</a>
    <a class="btn" href="<?= url('maps/' . (int) $map['map_id'] . '/export') ?>">⬇ Export JSON</a>
    <button class="btn btn-primary" type="button" id="save-layout"><span id="unsaved-dot" class="unsaved-dot" style="display:none"></span>💾 Save layout</button>
  </div>
</div>

<div class="editor-toolbar">
  <button class="btn btn-primary" type="button" id="add-room">+ Add room</button>
  <span class="editor-hint" id="editor-status">All changes saved.</span>
</div>

<div class="editor-layout">
  <div class="map-canvas card" style="margin-bottom:0">
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

  <aside class="card props-panel" id="props-panel">
    <h2>Room Properties</h2>
    <p class="muted" id="props-empty">Click a room on the map to edit it.</p>
    <div id="props-form" style="display:none">
      <label>Room name <input type="text" id="prop-name" maxlength="100"></label>
      <div class="props-row">
        <label>X <input type="number" id="prop-x" step="10" min="0"></label>
        <label>Y <input type="number" id="prop-y" step="10" min="0"></label>
      </div>
      <div class="props-row" id="prop-size-row">
        <label>Width <input type="number" id="prop-w" step="10" min="40"></label>
        <label>Height <input type="number" id="prop-h" step="10" min="40"></label>
      </div>
      <p class="editor-hint" id="prop-shape-note" style="display:none">Polygons move by dragging, but can't be resized here — use the room form for exact points.</p>
      <label>Description <textarea id="prop-desc" rows="2"></textarea></label>
      <label>Status
        <select id="prop-status">
          <option value="active">Active</option>
          <option value="unavailable">Unavailable</option>
        </select>
      </label>
      <p class="muted">Shape: <strong id="prop-shape">rectangle</strong></p>
      <div class="panel-actions">
        <button class="btn btn-danger" type="button" id="delete-room">Delete room</button>
      </div>
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
