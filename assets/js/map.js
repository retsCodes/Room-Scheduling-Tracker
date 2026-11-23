
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
      panelBody.innerHTML = '<p class="muted" style="margin-top:.6rem">No bookings today - free all day.</p>';
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

  var searchDataEl = document.getElementById('map-search-data');
  var searchRooms = [];
  try { searchRooms = JSON.parse(searchDataEl ? searchDataEl.textContent || '[]' : '[]'); } catch (e) {}
  if (!Array.isArray(searchRooms)) searchRooms = [];

  var searchInput = document.getElementById('room-search');
  var searchCount = document.getElementById('search-count');
  var searchNav = document.getElementById('search-nav');
  var searchPos = document.getElementById('search-position');
  var searchResults = document.getElementById('search-results');
  var matches = [];
  var matchIndex = -1;

  function currentFloorUrl(mapId, roomId) {
    var q = 'search=' + encodeURIComponent(searchInput ? searchInput.value.trim() : '') + '&room=' + roomId;
    var path = window.location.pathname;
    if (/\/maps\/\d+/.test(path)) {
      return path.replace(/\/maps\/\d+/, '/maps/' + mapId) + '?' + q;
    }
    return path + '?map=' + mapId + '&' + q;
  }
  function updateSearchUi() {
    if (!searchCount) return;
    if (!matches.length) {
      searchCount.textContent = searchInput && searchInput.value.trim() !== '' ? 'No rooms match.' : '';
      if (searchNav) searchNav.style.display = 'none';
      if (searchResults) searchResults.innerHTML = '';
      return;
    }
    searchCount.textContent = matches.length + (matches.length === 1 ? ' match' : ' matches');
    if (searchNav) searchNav.style.display = '';
    if (searchPos) searchPos.textContent = (matchIndex + 1) + ' of ' + matches.length;
    if (searchResults) {
      var html = '';
      matches.slice(0, 8).forEach(function (m, i) {
        var onThisFloor = shapes.some(function (s) { return +s.getAttribute('data-room-id') === m.room_id; });
        html += '<li><a href="#" data-match="' + i + '"' + (i === matchIndex ? ' style="font-weight:700"' : '') + '>' + escapeHtml(m.room_name) + ' <span class="muted">- ' + escapeHtml(m.map_name) + (onThisFloor ? '' : ' &rarr;') + '</span></a></li>';
      });
      if (matches.length > 8) html += '<li class="muted">+ ' + (matches.length - 8) + ' more - refine your search</li>';
      searchResults.innerHTML = html;
    }
  }
  function goToMatch(i) {
    if (!matches.length) return;
    matchIndex = ((i % matches.length) + matches.length) % matches.length;
    var m = matches[matchIndex];
    updateSearchUi();
    var shape = shapes.filter(function (s) { return +s.getAttribute('data-room-id') === m.room_id; })[0];
    if (shape) {
      selectRoom(shape);
    } else {
      window.location.assign(currentFloorUrl(m.map_id, m.room_id));
    }
  }
  function runSearch() {
    var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
    matches = q === '' ? [] : searchRooms.filter(function (r) { return (r.room_name || '').toLowerCase().indexOf(q) !== -1; });
    matchIndex = matches.length ? 0 : -1;
    shapes.forEach(function (shape) {
      var name = (shape.getAttribute('data-name') || '').toLowerCase();
      shape.classList.toggle('room-dim', q !== '' && name.indexOf(q) === -1);
    });
    updateSearchUi();
    return matches;
  }
  if (searchInput) {
    searchInput.addEventListener('input', runSearch);
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); goToMatch(matchIndex + 1); }
    });
  }
  if (searchResults) {
    searchResults.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a[data-match]') : null;
      if (!a) return;
      e.preventDefault();
      goToMatch(+a.getAttribute('data-match'));
    });
  }
  var prevBtn = document.getElementById('search-prev');
  var nextBtn = document.getElementById('search-next');
  if (prevBtn) prevBtn.addEventListener('click', function () { goToMatch(matchIndex - 1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { goToMatch(matchIndex + 1); });

  var selectedId = meta.selectedRoomId || 0;
  var first = null;
  if (selectedId) {
    first = shapes.filter(function (s) { return +s.getAttribute('data-room-id') === selectedId; })[0] || null;
  }
  if (!first) first = shapes.filter(function (s) { return s.getAttribute('data-status') === 'occupied'; })[0] || shapes[0];
  if (first) selectRoom(first);
  if (searchInput && searchInput.value.trim() !== '') {
    runSearch();
    if (selectedId) {
      var mi = -1;
      for (var k = 0; k < matches.length; k++) { if (matches[k].room_id === selectedId) { mi = k; break; } }
      if (mi >= 0) { matchIndex = mi; updateSearchUi(); }
    }
  }
})();
