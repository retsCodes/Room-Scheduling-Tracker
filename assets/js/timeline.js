(function () {
  var root = document.getElementById('timeline');
  var dataEl = document.getElementById('timeline-data');
  if (!root || !dataEl) return;
  var data = {};
  try { data = JSON.parse(dataEl.textContent || '{}'); } catch (e) {}
  var fullStart = data.start || 420;
  var fullEnd = data.end || 1140;
  var fullSpan = fullEnd - fullStart;
  var viewStart = fullStart;
  var viewEnd = fullEnd;
  var bookings = (data.bookings || []).slice().sort(function (a, b) { return a.start - b.start || a.end - b.end; });

  var gridEl = document.getElementById('tl-grid');
  var lanesEl = document.getElementById('tl-lanes');
  var scaleEl = document.getElementById('tl-scale');
  var nowEl = document.getElementById('tl-now');
  var selectEl = document.getElementById('tl-select');
  var rangeEl = document.getElementById('tl-range');
  var resetBtn = document.getElementById('tl-reset');

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function fmt(min) {
    var h = Math.floor(min / 60);
    var m = min % 60;
    var ampm = h >= 12 ? 'PM' : 'AM';
    var h12 = h % 12 || 12;
    return m ? h12 + ':' + String(m).padStart(2, '0') + ' ' + ampm : h12 + ' ' + ampm;
  }
  function pct(min) { return (min - viewStart) / (viewEnd - viewStart) * 100; }
  function clampView(s, e) {
    var span = e - s;
    if (span < 60) { var c = (s + e) / 2; s = c - 30; e = c + 30; span = 60; }
    if (span > fullSpan) { s = fullStart; e = fullEnd; }
    if (s < fullStart) { e += fullStart - s; s = fullStart; }
    if (e > fullEnd) { s -= e - fullEnd; e = fullEnd; }
    if (s < fullStart) s = fullStart;
    return [Math.round(s), Math.round(e)];
  }
  function setView(s, e) {
    var v = clampView(s, e);
    viewStart = v[0];
    viewEnd = v[1];
    render();
  }
  function tickStep() {
    var span = viewEnd - viewStart;
    return span > 360 ? 60 : span > 120 ? 30 : 15;
  }
  function lanesFor(visible) {
    var lanes = [];
    visible.forEach(function (b) {
      for (var i = 0; i < lanes.length; i++) {
        if (lanes[i] <= b.start) { lanes[i] = b.end; b.lane = i; return; }
      }
      lanes.push(b.end);
      b.lane = lanes.length - 1;
    });
    return Math.max(1, lanes.length);
  }
  function statusClass(s) {
    return s === 'cancelled' ? 'tl-red' : s === 'completed' ? 'tl-green' : 'tl-blue';
  }
  function render() {
    var span = viewEnd - viewStart;
    var step = tickStep();
    var grid = '';
    var scale = '';
    var firstTick = Math.ceil(viewStart / step) * step;
    for (var m = firstTick; m <= viewEnd; m += step) {
      grid += '<div class="tl-gridline" style="left:' + pct(m).toFixed(2) + '%"></div>';
      scale += '<span class="timeline-tick" style="left:' + pct(m).toFixed(2) + '%">' + fmt(m) + '</span>';
    }
    if (viewEnd % step !== 0) {
      grid += '<div class="tl-gridline" style="left:100%"></div>';
      scale += '<span class="timeline-tick" style="left:100%">' + fmt(viewEnd) + '</span>';
    }
    gridEl.innerHTML = grid;
    scaleEl.innerHTML = scale;

    var visible = bookings.filter(function (b) { return b.end > viewStart && b.start < viewEnd; });
    var laneCount = lanesFor(visible);
    var lanesHtml = '';
    for (var i = 0; i < laneCount; i++) {
      lanesHtml += '<div class="tl-lane">';
      visible.forEach(function (b) {
        if (b.lane !== i) return;
        var left = Math.max(0, pct(b.start));
        var right = Math.min(100, pct(b.end));
        var width = Math.max(1.5, right - left);
        var label = (b.confirmed ? '✓ ' : '') + b.title;
        if (width < 12) label = b.confirmed ? '✓' : '';
        lanesHtml += '<a class="timeline-block ' + statusClass(b.status) + (b.confirmed ? ' tl-confirmed' : '') + '" style="left:' + left.toFixed(1) + '%;width:' + width.toFixed(1) + '%" href="' + esc(b.url) + '" title="' + esc(b.title + ' ' + b.time + (b.confirmed ? ' - admin confirmed' : '')) + '">' + esc(label) + '</a>';
      });
      lanesHtml += '</div>';
    }
    lanesEl.innerHTML = lanesHtml;

    var now = data.now;
    if (typeof now === 'number' && now >= viewStart && now <= viewEnd) {
      nowEl.style.display = '';
      nowEl.style.left = pct(now).toFixed(2) + '%';
    } else {
      nowEl.style.display = 'none';
    }

    rangeEl.textContent = 'Showing ' + fmt(viewStart) + ' - ' + fmt(viewEnd) + (viewStart === fullStart && viewEnd === fullEnd ? '' : ' (full day ' + fmt(fullStart) + ' - ' + fmt(fullEnd) + ')');
    if (resetBtn) resetBtn.disabled = viewStart === fullStart && viewEnd === fullEnd;
    var presets = { all: [fullStart, fullEnd], morning: [420, 720], afternoon: [720, 1020], evening: [1020, 1320] };
    document.querySelectorAll('.tl-preset').forEach(function (btn) {
      var p = presets[btn.getAttribute('data-preset')] || [fullStart, fullEnd];
      btn.classList.toggle('active', viewStart === p[0] && viewEnd === p[1]);
    });
  }

  function zoom(factor) {
    var span = viewEnd - viewStart;
    var center = (viewStart + viewEnd) / 2;
    var next = Math.max(60, Math.min(fullSpan, Math.round(span * factor)));
    setView(center - next / 2, center + next / 2);
  }
  var zoomIn = document.getElementById('tl-zoom-in');
  var zoomOut = document.getElementById('tl-zoom-out');
  if (zoomIn) zoomIn.addEventListener('click', function () { zoom(0.5); });
  if (zoomOut) zoomOut.addEventListener('click', function () { zoom(2); });
  if (resetBtn) resetBtn.addEventListener('click', function () { setView(fullStart, fullEnd); });

  document.querySelectorAll('.tl-preset').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var ranges = { all: [fullStart, fullEnd], morning: [420, 720], afternoon: [720, 1020], evening: [1020, 1320] };
      var r = ranges[btn.getAttribute('data-preset')] || [fullStart, fullEnd];
      var s = Math.max(r[0], fullStart);
      var e = Math.min(r[1], fullEnd);
      if (e - s < 60) {
        var c = Math.max(fullStart + 30, Math.min(fullEnd - 30, (s + e) / 2));
        s = c - 30; e = c + 30;
      }
      if (e <= s) { s = fullStart; e = fullEnd; }
      setView(s, e);
    });
  });

  var drag = null;
  function minutesAt(clientX) {
    var rect = root.getBoundingClientRect();
    var x = Math.max(0, Math.min(rect.width, clientX - rect.left));
    return viewStart + x / rect.width * (viewEnd - viewStart);
  }
  root.addEventListener('pointerdown', function (e) {
    if (e.target.closest && e.target.closest('a')) return;
    drag = { start: minutesAt(e.clientX) };
    try { root.setPointerCapture(e.pointerId); } catch (err) {}
    e.preventDefault();
  });
  root.addEventListener('pointermove', function (e) {
    if (!drag) return;
    var cur = minutesAt(e.clientX);
    var s = Math.min(drag.start, cur);
    var en = Math.max(drag.start, cur);
    selectEl.style.display = '';
    selectEl.style.left = pct(s).toFixed(2) + '%';
    selectEl.style.width = Math.max(0, pct(en) - pct(s)).toFixed(2) + '%';
    drag.end = cur;
  });
  function endDrag() {
    if (!drag) return;
    var s = drag.start;
    var en = (drag.end == null ? s : drag.end);
    drag = null;
    selectEl.style.display = 'none';
    if (Math.abs(en - s) < 30) return;
    var lo = Math.floor(Math.min(s, en) / 15) * 15;
    var hi = Math.ceil(Math.max(s, en) / 15) * 15;
    if (hi - lo < 30) hi = lo + 30;
    setView(lo, hi);
  }
  root.addEventListener('pointerup', endDrag);
  root.addEventListener('pointercancel', function () { drag = null; selectEl.style.display = 'none'; });

  render();
})();
