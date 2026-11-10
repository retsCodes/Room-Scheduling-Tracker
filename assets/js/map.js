
(function () {
  var bookingsEl = document.getElementById('room-bookings');
  var metaEl = document.getElementById('room-meta');
  if (!bookingsEl || !metaEl) return;
  var bookings = {};
  var meta = { canBook: false, bookUrl: '', roomUrl: '' };
  try { bookings = JSON.parse(bookingsEl.textContent || '{}'); } catch (e) {}
  try { meta = JSON.parse(metaEl.textContent || '{}'); } catch (e) {}

  var shapes = Array.prototype.slice.call(document.querySelectorAll('.room-shape'));
  var panelTitle = document.getElementById('panel-title');
  var panelDesc = document.getElementById('panel-desc');
  var panelBody = document.getElementById('panel-body');
  var panelActions = document.getElementById('panel-actions');

  function statusBadge(status) {
    var map = { available: ['badge-green', 'Available'], occupied: ['badge-red', 'Occupied now'], reserved: ['badge-amber', 'Reserved today'], unavailable: ['badge-grey', 'Unavailable'] };
    var b = map[status] || ['badge-grey', status];
    return '<span class="badge ' + b[0] + '">' + b[1] + '</span>';
  }

  function selectRoom(shape) {
    shapes.forEach(function (s) { s.classList.remove('selected'); });
    shape.classList.add('selected');
    var id = shape.getAttribute('data-room-id');
    var name = shape.getAttribute('data-name') || '';
    var desc = shape.getAttribute('data-desc') || '';
    var status = shape.getAttribute('data-status') || '';
    panelTitle.textContent = name;
    panelDesc.innerHTML = statusBadge(status) + (desc ? ' ' + escapeHtml(desc) : '');
    var list = bookings[id] || [];
    if (!list.length) {
      panelBody.innerHTML = '<p class="muted" style="margin-top:.6rem">No bookings today — free all day.</p>';
    } else {
      var html = '<ul class="clean-list" style="margin-top:.6rem">';
      list.forEach(function (b) {
        var badge = b.status === 'scheduled' ? 'badge-blue' : (b.status === 'cancelled' ? 'badge-red' : 'badge-green');
        html += '<li><strong>' + escapeHtml(b.start) + '–' + escapeHtml(b.end) + '</strong> '
          + '<a href="' + escapeHtml(meta.roomUrl) + id + '">' + escapeHtml(b.title) + '</a> '
          + '<span class="badge ' + badge + '">' + escapeHtml(b.status) + '</span></li>';
      });
      html += '</ul>';
      panelBody.innerHTML = html;
    }
    var actions = '';
    actions += '<a class="btn" href="' + escapeHtml(meta.roomUrl) + id + '">Room details →</a>';
    if (meta.canBook && status !== 'unavailable') {
      actions += '<a class="btn btn-primary" href="' + escapeHtml(meta.bookUrl) + '">Book this room</a>';
    }
    panelActions.innerHTML = actions;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  shapes.forEach(function (shape) {
    shape.addEventListener('click', function () {
      window.location.href = meta.roomUrl + shape.getAttribute('data-room-id');
    });
    shape.addEventListener('mouseenter', function () { selectRoom(shape); });
  });

  var search = document.getElementById('room-search');
  if (search) {
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      shapes.forEach(function (shape) {
        var name = (shape.getAttribute('data-name') || '').toLowerCase();
        shape.classList.toggle('room-dim', q !== '' && name.indexOf(q) === -1);
      });
    });
  }

  var first = shapes.filter(function (s) { return s.getAttribute('data-status') === 'occupied'; })[0] || shapes[0];
  if (first) selectRoom(first);
})();
