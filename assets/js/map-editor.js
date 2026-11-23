
(function () {
  var svg = document.getElementById('editor-svg');
  var roomsEl = document.getElementById('editor-rooms');
  var metaEl = document.getElementById('editor-meta');
  if (!svg || !roomsEl || !metaEl) return;

  var rooms = [];
  var meta = {};
  try { rooms = JSON.parse(roomsEl.textContent || '[]'); } catch (e) {}
  try { meta = JSON.parse(metaEl.textContent || '{}'); } catch (e) {}
  var originalRooms = JSON.parse(JSON.stringify(rooms));
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
    if (r.shape_type === 'polygon') {
      (d.points || []).forEach(function (p, i) {
        html += '<circle class="editor-vertex" data-room-id="' + r.room_id + '" data-vertex="' + i + '" cx="' + (+p.x || 0) + '" cy="' + (+p.y || 0) + '" r="7" />';
      });
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
      } else if (r.shape_type === 'path' && d.d) {
        shape.setAttribute('d', d.d);
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
    if (r.shape_type === 'polygon') {
      var vertices = g.querySelectorAll('.editor-vertex');
      var pts = d.points || [];
      if (vertices.length === pts.length) {
        vertices.forEach(function (v, i) {
          v.setAttribute('cx', +pts[i].x || 0);
          v.setAttribute('cy', +pts[i].y || 0);
        });
      } else {
        renderAll();
      }
    }
  }

  var discardBtn = document.getElementById('discard-layout');
  function setUnsaved(v) {
    unsaved = v;
    unsavedDot.style.display = v ? 'inline-block' : 'none';
    if (discardBtn) discardBtn.disabled = !v;
  }
  function markUnsaved() {
    setUnsaved(true);
    statusEl.textContent = 'Unsaved changes.';
  }
  function boundsOf(r) {
    var d = r.shape_data || {};
    if (r.shape_type === 'rectangle') {
      return { x: +d.x || 0, y: +d.y || 0, w: +d.width || 100, h: +d.height || 80 };
    }
    var pts = d.points || [];
    if (pts.length) {
      var xs = pts.map(function (p) { return +p.x || 0; });
      var ys = pts.map(function (p) { return +p.y || 0; });
      var minX = Math.min.apply(null, xs), maxX = Math.max.apply(null, xs);
      var minY = Math.min.apply(null, ys), maxY = Math.max.apply(null, ys);
      return { x: minX, y: minY, w: Math.max(40, maxX - minX), h: Math.max(40, maxY - minY) };
    }
    var c = centre(r);
    return { x: c[0] - 80, y: c[1] - 50, w: 160, h: 100 };
  }
  function changeShape(r, type) {
    var b = boundsOf(r);
    if (type === 'rectangle') {
      r.shape_data = { x: Math.round(b.x), y: Math.round(b.y), width: Math.max(40, Math.round(b.w)), height: Math.max(40, Math.round(b.h)) };
    } else if (type === 'polygon') {
      r.shape_data = { points: [
        { x: Math.round(b.x), y: Math.round(b.y) },
        { x: Math.round(b.x + b.w), y: Math.round(b.y) },
        { x: Math.round(b.x + b.w), y: Math.round(b.y + b.h) },
        { x: Math.round(b.x), y: Math.round(b.y + b.h) },
      ] };
    }
    r.shape_type = type;
  }
  function snap(v) { return Math.round(v / 10) * 10; }

  
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
    var d = r.shape_data || {};
    var shapeSel = document.getElementById('prop-shape');
    shapeSel.disabled = r.shape_type === 'path';
    if (r.shape_type !== 'path') shapeSel.value = r.shape_type;
    var b = boundsOf(r);
    var isPath = r.shape_type === 'path';
    document.getElementById('prop-w').disabled = isPath;
    document.getElementById('prop-h').disabled = isPath;
    document.getElementById('prop-x').disabled = isPath;
    document.getElementById('prop-y').disabled = isPath;
    var note = document.getElementById('prop-shape-note');
    if (isPath) {
      note.textContent = 'This shape cannot be edited here - convert it to a polygon to reshape it.';
      note.style.display = '';
    } else if (r.shape_type === 'polygon') {
      note.textContent = 'Drag the corner dots to reshape, or change Width / Height to scale it.';
      note.style.display = '';
    } else {
      note.style.display = 'none';
    }
    document.getElementById('add-corner').style.display = r.shape_type === 'polygon' ? '' : 'none';
    document.getElementById('convert-polygon').style.display = isPath ? '' : 'none';
    if (r.shape_type === 'rectangle') {
      document.getElementById('prop-x').value = +d.x || 0;
      document.getElementById('prop-y').value = +d.y || 0;
    } else {
      document.getElementById('prop-x').value = Math.round(b.x + b.w / 2);
      document.getElementById('prop-y').value = Math.round(b.y + b.h / 2);
    }
    document.getElementById('prop-w').value = Math.round(b.w);
    document.getElementById('prop-h').value = Math.round(b.h);
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
  bindProp('prop-w', function (r, v) {
    var n = Math.max(40, +v || 40);
    if (r.shape_type === 'rectangle') { r.shape_data.width = n; return; }
    if (r.shape_type !== 'polygon') return;
    var b = boundsOf(r);
    var s = n / Math.max(1, b.w);
    var cx = b.x + b.w / 2;
    (r.shape_data.points || []).forEach(function (p) { p.x = Math.max(0, Math.round(cx + ((+p.x || 0) - cx) * s)); });
  });
  bindProp('prop-h', function (r, v) {
    var n = Math.max(40, +v || 40);
    if (r.shape_type === 'rectangle') { r.shape_data.height = n; return; }
    if (r.shape_type !== 'polygon') return;
    var b = boundsOf(r);
    var s = n / Math.max(1, b.h);
    var cy = b.y + b.h / 2;
    (r.shape_data.points || []).forEach(function (p) { p.y = Math.max(0, Math.round(cy + ((+p.y || 0) - cy) * s)); });
  });
  document.getElementById('add-corner').addEventListener('click', function () {
    var r = findRoom(selectedId);
    if (!r || r.shape_type !== 'polygon') return;
    var pts = r.shape_data.points || [];
    if (pts.length < 3) return;
    var best = 0, bestLen = -1;
    for (var i = 0; i < pts.length; i++) {
      var a = pts[i], b = pts[(i + 1) % pts.length];
      var len = Math.hypot((+b.x || 0) - (+a.x || 0), (+b.y || 0) - (+a.y || 0));
      if (len > bestLen) { bestLen = len; best = i; }
    }
    var p1 = pts[best], p2 = pts[(best + 1) % pts.length];
    pts.splice(best + 1, 0, { x: Math.round(((+p1.x || 0) + (+p2.x || 0)) / 2), y: Math.round(((+p1.y || 0) + (+p2.y || 0)) / 2) });
    renderAll();
    select(r.room_id);
    markUnsaved();
  });
  document.getElementById('convert-polygon').addEventListener('click', function () {
    var r = findRoom(selectedId);
    if (!r || r.shape_type !== 'path') return;
    changeShape(r, 'polygon');
    renderAll();
    select(r.room_id);
    markUnsaved();
  });
  document.getElementById('prop-shape').addEventListener('change', function (e) {
    var r = findRoom(selectedId);
    if (!r) return;
    changeShape(r, e.target.value);
    renderAll();
    select(r.room_id);
    markUnsaved();
  });

  document.getElementById('delete-room').addEventListener('click', function () {
    var r = findRoom(selectedId);
    if (!r) return;
    window.showConfirm('Delete room "' + r.room_name + '"?', function () {
      if (r.room_id > 0) deleted.push(r.room_id);
      rooms = rooms.filter(function (x) { return x.room_id !== selectedId; });
      selectedId = null;
      propsForm.style.display = 'none'; propsEmpty.style.display = '';
      renderAll();
      markUnsaved();
    });
  });

  document.getElementById('discard-layout').addEventListener('click', function () {
    if (!unsaved) return;
    window.showConfirm('Discard all unsaved changes?', function () {
      rooms = JSON.parse(JSON.stringify(originalRooms));
      deleted = [];
      selectedId = null;
      setUnsaved(false);
      statusEl.textContent = 'All changes saved.';
      propsForm.style.display = 'none'; propsEmpty.style.display = '';
      document.getElementById('prop-name').value = '';
      document.getElementById('prop-desc').value = '';
      document.getElementById('prop-status').value = 'active';
      document.getElementById('prop-shape').value = 'rectangle';
      document.getElementById('prop-x').value = '';
      document.getElementById('prop-y').value = '';
      document.getElementById('prop-w').value = '';
      document.getElementById('prop-h').value = '';
      renderAll();
    });
  });

  
  var tool = 'select';
  var toolSelectBtn = document.getElementById('tool-select');
  var toolAddBtn = document.getElementById('tool-add');
  function setTool(t) {
    tool = t;
    if (toolSelectBtn) toolSelectBtn.classList.toggle('active', t === 'select');
    if (toolAddBtn) toolAddBtn.classList.toggle('active', t === 'add');
  }
  if (toolSelectBtn) toolSelectBtn.addEventListener('click', function () { setTool('select'); });
  if (toolAddBtn) toolAddBtn.addEventListener('click', function () { setTool('add'); });

  var zoom = 1;
  function applyZoom() {
    svg.style.width = Math.round(zoom * 100) + '%';
    svg.style.height = 'auto';
  }
  var zoomIn = document.getElementById('zoom-in');
  var zoomOut = document.getElementById('zoom-out');
  if (zoomIn) zoomIn.addEventListener('click', function () { zoom = Math.min(2.5, zoom + 0.25); applyZoom(); });
  if (zoomOut) zoomOut.addEventListener('click', function () { zoom = Math.max(0.5, zoom - 0.25); applyZoom(); });
  var fsBtn = document.getElementById('fullscreen-btn');
  var fsWrap = document.getElementById('editor-map-wrap');
  if (fsBtn && fsWrap) {
    fsBtn.addEventListener('click', function () {
      if (document.fullscreenElement) { document.exitFullscreen(); }
      else if (fsWrap.requestFullscreen) { fsWrap.requestFullscreen(); }
    });
  }

  function addRoomAt(p) {
    var r = { room_id: tempId--, room_name: 'New Room', shape_type: 'rectangle',
      shape_data: { x: Math.max(0, snap(p.x - 80)), y: Math.max(0, snap(p.y - 50)), width: 160, height: 100 },
      room_description: '', room_status: 'active' };
    rooms.push(r);
    renderAll();
    select(r.room_id);
    markUnsaved();
  }

  
  function svgPoint(evt) {
    var pt = svg.createSVGPoint();
    pt.x = evt.clientX; pt.y = evt.clientY;
    return pt.matrixTransform(svg.getScreenCTM().inverse());
  }
  var drag = null;
  svg.addEventListener('pointerdown', function (e) {

    if (tool === 'add' && e.target === svg) {
      addRoomAt(svgPoint(e));
      setTool('select');
      e.preventDefault();
      return;
    }
    var vertex = e.target.closest ? e.target.closest('.editor-vertex') : null;
    var handle = e.target.closest ? e.target.closest('.editor-handle') : null;
    var group = e.target.closest ? e.target.closest('.editor-room') : null;
    if (!group) return;
    var id = +group.getAttribute('data-room-id');
    var r = findRoom(id);
    if (!r) return;
    select(id);
    if (r.shape_type === 'path') return;
    var p = svgPoint(e);
    var mode = vertex ? 'vertex' : (handle ? 'resize' : 'move');
    drag = {
      id: id, mode: mode,
      vertex: vertex ? +vertex.getAttribute('data-vertex') : -1,
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
    if (drag.mode === 'vertex') {
      var pts = d.points || [];
      var pt = pts[drag.vertex];
      var origPt = (drag.orig.points || [])[drag.vertex];
      if (pt && origPt) {
        pt.x = Math.max(0, (+origPt.x || 0) + dx);
        pt.y = Math.max(0, (+origPt.y || 0) + dy);
      }
    } else if (drag.mode === 'move') {
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

    var b = boundsOf(r);
    if (r.shape_type === 'rectangle') {
      document.getElementById('prop-x').value = +d.x || 0;
      document.getElementById('prop-y').value = +d.y || 0;
    } else {
      document.getElementById('prop-x').value = Math.round(b.x + b.w / 2);
      document.getElementById('prop-y').value = Math.round(b.y + b.h / 2);
    }
    document.getElementById('prop-w').value = Math.round(b.w);
    document.getElementById('prop-h').value = Math.round(b.h);
    markUnsaved();
  });
  function endDrag() { drag = null; }
  svg.addEventListener('pointerup', endDrag);
  svg.addEventListener('pointercancel', endDrag);

  
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
        setUnsaved(false);
        statusEl.textContent = 'Saved (' + data.saved + ' updated, ' + data.created + ' new, ' + data.deleted + ' deleted).';
        setTimeout(function () { window.location.reload(); }, 700);
      })
      .catch(function (err) { statusEl.textContent = 'Save failed: ' + err.message; });
  });

  window.addEventListener('beforeunload', function (e) {
    if (unsaved) { e.preventDefault(); e.returnValue = ''; }
  });

  renderAll();
})();
