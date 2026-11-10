<h1>Edit user — <?= e($user['username']) ?></h1>
<form method="post" action="<?= url('users/' . (int) $user['user_id']) ?>" class="card form">
  <?= csrf_field() ?>
  <label>Username <input type="text" name="username" required value="<?= e($user['username']) ?>"></label>
  <label>Email <input type="email" name="email" required value="<?= e($user['email']) ?>"></label>
  <label>Role
    <select name="role">
      <?php foreach (['admin','teacher','student','officer','mayor','scheduler','viewer'] as $r): ?>
        <option <?= $user['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Section <input type="text" name="section" value="<?= e($user['section'] ?? '') ?>"></label>
  <label>Organization name <input type="text" name="org_name" value="<?= e($user['org_name'] ?? '') ?>"></label>
  <label>Phone number <input type="tel" name="phone_number" value="<?= e($user['phone_number'] ?? '') ?>"></label>
  <label>New password (leave blank to keep current) <input type="password" name="new_password" minlength="8"></label>
  <button class="btn btn-primary" type="submit">Save changes</button>
  <a class="btn" href="<?= url('users') ?>">Cancel</a>
</form>
<form method="post" action="<?= url('users/' . (int) $user['user_id'] . '/delete') ?>" class="card form danger-zone" onsubmit="return confirm('Delete this user?')">
  <?= csrf_field() ?><button class="btn btn-danger" type="submit">Delete user</button>
</form>
