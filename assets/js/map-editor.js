/* Admin map editor: drag rooms to move, drag the corner dot to resize
   rectangles, edit properties live. Everything updates on the map as you
   work; Save layout posts the whole layout to the server. */
(function () {
  var svg = document.getElementById('editor-svg');
  var roomsEl = document.getElementById('editor-rooms');
  var metaEl = document.getElementById('editor-meta');
  if (!svg || !roomsEl || !metaEl) return;

  var rooms = [];
  var meta = {};
  try { rooms = JSON.parse(roomsEl.textContent || '[]'); } catch (e) {}
  try { meta = JSON.parse(metaEl.textContent || '{}'); } catch (e) {}
  var deleted = [];
  var selectedId = null;
  var tempId = -1;
  var unsaved = false;

  var statusEl = document.getElementById('editor-status');
  var unsavedDot = document.getElementById('unsaved-dot');
  var propsForm = document.getElementById('props-form');
  var propsEmpty = document.getElementById('props-empty');

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function fillFor(r) { return r.room_status === 'unavailable' ? '#e2e8f0' : '#bbf7d0'; }
  function strokeFor(r) { return r.room_status === 'unavailable' ? '#94a3b8' : '#16a34a'; }
  function centre(r) {
    var d = r.shape_data || {};
    if (r.shape_type === 'rectangle') {
      return [(+d.x || 0) + (+d.width || 100) / 2, (+d.y || 0) + (+d.height || 80) / 2];
    }
    var xs = [], ys = [];
    (d.points || []).forEach(function (p) { xs.push(+p.x || 0); ys.push(+p.y || 0); });
    if (!xs.length) return [50, 50];
    return [(Math.min.apply(null, xs) + Math.max.apply(null, xs)) / 2,
            (Math.min.apply(null, ys) + Math.max.apply(null, ys)) / 2];
  }
  function shapeHtml(r) {
    var d = r.shape_data || {};
    var common = 'fill="' + fillFor(r) + '" stroke="' + strokeFor(r) + '" stroke-width="2"';
    if (r.shape_type === 'rectangle') {
      return '<rect x="' + (+d.x || 0) + '" y="' + (+d.y || 0) + '" width="' + (+d.width || 100) + '" height="' + (+d.height || 80) + '" rx="6" ' + common + ' />';
    }
    if (r.shape_type === 'path' && d.d) {
      return '<path d="' + esc(d.d) + '" ' + common + ' />';
    }
    var pts = (d.points || []).map(function (p) { return (+p.x || 0) + ',' + (+p.y || 0); }).join(' ');
    return '<polygon points="' + esc(pts) + '" ' + common + ' />';
  }
  function roomGroupHtml(r) {
    var c = centre(r);
    var d = r.shape_data || {};
    var html = '<g class="editor-room' + (r.room_id === selectedId ? ' selected' : '') + '" data-room-id="' + r.room_id + '">';
    html += shapeHtml(r);
    html += '<text class="room-label" x="' + c[0] + '" y="' + (c[1] - 2) + '" text-anchor="middle">' + esc(r.room_name) + '</text>';
    html += '<text class="room-sub" x="' + c[0] + '" y="' + (c[1] + 14) + '" text-anchor="middle">' + esc(r.room_status) + '</text>';
    if (r.shape_type === 'rectangle') {
      html += '<circle class="editor-handle" data-room-id="' + r.room_id + '" cx="' + ((+d.x || 0) + (+d.width || 100)) + '" cy="' + ((+d.y || 0) + (+d.height || 80)) + '" r="9" />';
    }
    return html + '</g>';
  }
  function renderAll() {
    svg.innerHTML = rooms.map(roomGroupHtml).join('');
  }
  function findRoom(id) { return rooms.filter(function (r) { return r.room_id === id; })[0]; }
  function groupFor(id) { return svg.querySelector('g[data-room-id="' + id + '"]'); }

  function updateRoomDom(r) {
    var g = groupFor(r.room_id);
    if (!g) { renderAll(); return; }
    var d = r.shape_data || {};
    var shape = g.querySelector('rect, polygon, path');
    if (shape) {
      shape.setAttribute('fill', fillFor(r));
      shape.setAttribute('stroke', strokeFor(r));
      if (r.shape_type === 'rectangle') {
        shape.setAttribute('x', +d.x || 0); shape.setAttribute('y', +d.y || 0);
        shape.setAttribute('width', +d.width || 100); shape.setAttribute('height', +d.height || 80);
      } else if (r.shape_type === 'polygon') {
        shape.setAttribute('points', (d.points || []).map(function (p) { return (+p.x || 0) + ',' + (+p.y || 0); }).join(' '));
      }
    }
    var c = centre(r);
    var label = g.querySelector('.room-label');
    var sub = g.querySelector('.room-sub');
    if (label) { label.setAttribute('x', c[0]); label.setAttribute('y', c[1] - 2); label.textContent = r.room_name; }
    if (sub) { sub.setAttribute('x', c[0]); sub.setAttribute('y', c[1] + 14); sub.textContent = r.room_status; }
    var handle = g.querySelector('.editor-handle');
    if (handle && r.shape_type === 'rectangle') {
      handle.setAttribute('cx', (+d.x || 0) + (+d.width || 100));
      handle.setAttribute('cy', (+d.y || 0) + (+d.height || 80));
    }
  }

  function markUnsaved() {
    unsaved = true;
    unsavedDot.style.display = 'inline-block';
    statusEl.textContent = 'Unsaved changes — hit Save layout.';
  }
  function snap(v) { return Math.round(v / 10) * 10; }

  /* --- selection + properties panel ------------------------------------ */
  function select(id) {
    selectedId = id;
    svg.querySelectorAll('.editor-room').forEach(function (g) {
      g.classList.toggle('selected', +g.getAttribute('data-room-id') === id);
    });
    var r = findRoom(id);
    if (!r) { propsForm.style.display = 'none'; propsEmpty.style.display = ''; return; }
    propsEmpty.style.display = 'none';
    propsForm.style.display = '';
    document.getElementById('prop-name').value = r.room_name;
    document.getElementById('prop-desc').value = r.room_description || '';
    document.getElementById('prop-status').value = r.room_status;
    document.getElementById('prop-shape').textContent = r.shape_type;
    var d = r.shape_data || {};
    var isRect = r.shape_type === 'rectangle';
    document.getElementById('prop-size-row').style.display = isRect ? '' : 'none';
    document.getElementById('prop-shape-note').style.display = isRect ? 'none' : '';
    if (isRect) {
      document.getElementById('prop-x').value = +d.x || 0;
      document.getElementById('prop-y').value = +d.y || 0;
      document.getElementById('prop-w').value = +d.width || 100;
      document.getElementById('prop-h').value = +d.height || 80;
    } else {
      var c = centre(r);
      document.getElementById('prop-x').value = Math.round(c[0]);
      document.getElementById('prop-y').value = Math.round(c[1]);
    }
  }
  function bindProp(id, fn) {
    document.getElementById(id).addEventListener('input', function (e) {
      var r = findRoom(selectedId);
      if (!r) return;
      fn(r, e.target.value);
      updateRoomDom(r);
      markUnsaved();
    });
  }
  bindProp('prop-name', function (r, v) { r.room_name = v; });
  bindProp('prop-desc', function (r, v) { r.room_description = v; });
  bindProp('prop-status', function (r, v) { r.room_status = v; });
  bindProp('prop-x', function (r, v) {
    var n = Math.max(0, +v || 0); var d = r.shape_data || {};
    if (r.shape_type === 'rectangle') { d.x = n; }
    else { var c = centre(r); var dx = n - c[0]; (d.points || []).forEach(function (p) { p.x = (+p.x || 0) + dx; }); }
  });
  bindProp('prop-y', function (r, v) {
    var n = Math.max(0, +v || 0); var d = r.shape_data || {};
    if (r.shape_type === 'rectangle') { d.y = n; }
    else { var c = centre(r); var dy = n - c[1]; (d.points || []).forEach(function (p) { p.y = (+p.y || 0) + dy; }); }
  });
  bindProp('prop-w', function (r, v) { if (r.shape_type === 'rectangle') r.shape_data.width = Math.max(40, +v || 40); });
  bindProp('prop-h', function (r, v) { if (r.shape_type === 'rectangle') r.shape_data.height = Math.max(40, +v || 40); });

  document.getElementById('delete-room').addEventListener('click', function () {
    var r = findRoom(selectedId);
    if (!r || !confirm('Delete room "' + r.room_name + '"?')) return;
    if (r.room_id > 0) deleted.push(r.room_id);
    rooms = rooms.filter(function (x) { return x.room_id !== selectedId; });
    selectedId = null;
    propsForm.style.display = 'none'; propsEmpty.style.display = '';
    renderAll();
    markUnsaved();
  });

  document.getElementById('add-room').addEventListener('click', function () {
    var r = { room_id: tempId--, room_name: 'New Room', shape_type: 'rectangle',
      shape_data: { x: 60, y: 60, width: 160, height: 100 }, room_description: '', room_status: 'active' };
    rooms.push(r);
    renderAll();
    select(r.room_id);
    markUnsaved();
  });

  /* --- dragging ---------------------------------------------------------- */
  function svgPoint(evt) {
    var pt = svg.createSVGPoint();
    pt.x = evt.clientX; pt.y = evt.clientY;
    return pt.matrixTransform(svg.getScreenCTM().inverse());
  }
  var drag = null;
  svg.addEventListener('pointerdown', function (e) {
    var handle = e.target.closest ? e.target.closest('.editor-handle') : null;
    var group = e.target.closest ? e.target.closest('.editor-room') : null;
    if (!group) return;
    var id = +group.getAttribute('data-room-id');
    var r = findRoom(id);
    if (!r || r.shape_type === 'path') return;
    select(id);
    var p = svgPoint(e);
    drag = {
      id: id, mode: handle ? 'resize' : 'move',
      startX: p.x, startY: p.y,
      orig: JSON.parse(JSON.stringify(r.shape_data || {})),
    };
    svg.setPointerCapture(e.pointerId);
    e.preventDefault();
  });
  svg.addEventListener('pointermove', function (e) {
    if (!drag) return;
    var r = findRoom(drag.id);
    if (!r) return;
    var p = svgPoint(e);
    var dx = snap(p.x - drag.startX);
    var dy = snap(p.y - drag.startY);
    var d = r.shape_data || {};
    if (drag.mode === 'move') {
      if (r.shape_type === 'rectangle') {
        d.x = Math.max(0, (+drag.orig.x || 0) + dx);
        d.y = Math.max(0, (+drag.orig.y || 0) + dy);
      } else {
        d.points = (drag.orig.points || []).map(function (pt) {
          return { x: Math.max(0, (+pt.x || 0) + dx), y: Math.max(0, (+pt.y || 0) + dy) };
        });
      }
    } else {
      d.width = Math.max(40, (+drag.orig.width || 100) + dx);
      d.height = Math.max(40, (+drag.orig.height || 80) + dy);
    }
    updateRoomDom(r);
    // Keep the properties panel in sync while dragging.
    if (r.shape_type === 'rectangle') {
      document.getElementById('prop-x').value = +d.x || 0;
      document.getElementById('prop-y').value = +d.y || 0;
      document.getElementById('prop-w').value = +d.width || 100;
      document.getElementById('prop-h').value = +d.height || 80;
    }
    markUnsaved();
  });
  function endDrag() { drag = null; }
  svg.addEventListener('pointerup', endDrag);
  svg.addEventListener('pointercancel', endDrag);

  /* --- save -------------------------------------------------------------- */
  document.getElementById('save-layout').addEventListener('click', function () {
    var fd = new FormData();
    fd.append('_csrf', meta.csrf || '');
    fd.append('layout', JSON.stringify(rooms));
    fd.append('deleted', JSON.stringify(deleted));
    statusEl.textContent = 'Saving…';
    fetch(meta.saveUrl, { method: 'POST', body: fd })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.ok) { statusEl.textContent = 'Save failed: ' + (data.error || 'unknown error'); return; }
        unsaved = false;
        unsavedDot.style.display = 'none';
        statusEl.textContent = 'Saved ✓ (' + data.saved + ' updated, ' + data.created + ' new, ' + data.deleted + ' deleted) — reloading…';
        setTimeout(function () { window.location.reload(); }, 700);
      })
      .catch(function (err) { statusEl.textContent = 'Save failed: ' + err.message; });
  });

  window.addEventListener('beforeunload', function (e) {
    if (unsaved) { e.preventDefault(); e.returnValue = ''; }
  });

  renderAll();
})();
