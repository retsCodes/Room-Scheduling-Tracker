<p class="page-label">ADMIN</p>
<div class="page-head">
  <div>
    <h1>Admin dashboard</h1>
    <p class="muted">Manage maps, rooms, schedules and users.</p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a class="btn" href="<?= url('rooms/create') ?>">+ New room</a>
    <a class="btn btn-primary" href="<?= url('schedules/create') ?>">+ New schedule</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card tint-amber"><div class="stat-icon"></div><div><strong><?= (int) $stats['unconfirmed'] ?></strong><span>Awaiting confirmation</span></div></div>
  <div class="stat-card tint-green"><div class="stat-icon"></div><div><strong><?= (int) $stats['free_now'] ?></strong><span>Free right now</span></div></div>
  <div class="stat-card tint-red"><div class="stat-icon"></div><div><strong><?= (int) $stats['busy_now'] ?></strong><span>Occupied now</span></div></div>
  <div class="stat-card tint-amber"><div class="stat-icon"></div><div><strong><?= (int) $stats['today'] ?></strong><span>Bookings today</span></div></div>
  <div class="stat-card tint-purple"><div class="stat-icon"></div><div><strong><?= (int) $stats['users'] ?></strong><span>Users</span></div></div>
  <div class="stat-card tint-purple"><div class="stat-icon"></div><div><strong><?= (int) $stats['rooms'] ?></strong><span>Rooms</span></div></div>
</div>

<section class="card">
  <h2>Awaiting confirmation</h2>
  <?php if (empty($unconfirmed)): ?>
    <p class="muted">Nothing waiting - every active schedule is confirmed.</p>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Schedule</th><th>Room</th><th>Organizer</th><th>Date</th><th>Time</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($unconfirmed as $s): ?>
        <tr>
          <td><a href="<?= url('schedules/' . (int) $s['schedule_id']) ?>"><?= e($s['schedule_title']) ?></a></td>
          <td><?= e($s['room_name']) ?></td>
          <td><?= e($s['organizer_name']) ?></td>
          <td><?= e(date('D, M j', strtotime($s['start_date']))) ?></td>
          <td><?= time_12h($s['start_time']) ?>–<?= time_12h($s['end_time']) ?></td>
          <td>
            <form method="post" action="<?= url('schedules/' . (int) $s['schedule_id'] . '/confirm') ?>" class="inline-form">
              <?= csrf_field() ?>
              <button class="btn btn-small btn-primary" type="submit">Confirm</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="muted">Confirming one booking never cancels the others - overlapping bookings stay active.</p>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Users</h2>
  <table class="table">
    <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Section</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['username']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><?= e($u['role']) ?></td>
        <td><?= e($u['section'] ?? '') ?></td>
        <td><a href="<?= url('users/' . (int) $u['user_id'] . '/edit') ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p><a class="btn" href="<?= url('users') ?>">View all users →</a></p>
</section>
