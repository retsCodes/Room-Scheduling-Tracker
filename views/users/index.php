<h1>Users</h1>
<p class="muted">Admin only. New accounts register as students; change roles here.</p>
<table class="table card">
  <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Section</th><th>Organization</th><th>Phone</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr>
      <td><?= e($u['username']) ?></td>
      <td><?= e($u['email']) ?></td>
      <td><?= e($u['role']) ?></td>
      <td><?= e($u['section'] ?? '') ?></td>
      <td><?= e($u['org_name'] ?? '') ?></td>
      <td><?= e($u['phone_number'] ?? '') ?></td>
      <td><a href="<?= url('users/' . (int) $u['user_id'] . '/edit') ?>">Edit</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
