<?php $isAdmin = ($scope ?? 'student') === 'admin'; ?>
<h1><?= $isAdmin ? 'Admin log in' : 'Student log in' ?></h1>
<p class="muted">
  <?= $isAdmin
    ? 'For administrators only — manage maps, rooms, schedules and users.'
    : 'For students and teachers — book rooms and view your schedules.' ?>
  <?php if ($isAdmin): ?>
    Demo account: <code>admin</code> / <code>password123</code> (after importing <code>database/seed.sql</code>).
  <?php else: ?>
    Demo account: <code>ana</code> / <code>password123</code> (student) or <code>jdoe</code> / <code>password123</code> (teacher).
  <?php endif; ?>
</p>
<form method="post" action="<?= url('login/' . ($isAdmin ? 'admin' : 'student')) ?>" class="card form">
  <?= csrf_field() ?>
  <label>Username or email
    <input type="text" name="login" required value="<?= old('login') ?>" autofocus>
  </label>
  <label>Password
    <input type="password" name="password" required>
  </label>
  <button class="btn btn-primary" type="submit">Log in as <?= $isAdmin ? 'admin' : 'student' ?></button>
  <p class="muted">
    <?php if ($isAdmin): ?>
      Not an admin? <a href="<?= url('login/student') ?>">Go to the student login →</a>
    <?php else: ?>
      Are you an admin? <a href="<?= url('login/admin') ?>">Go to the admin login →</a><br>
      No account yet? <a href="<?= url('register') ?>">Create a student account</a>.
    <?php endif; ?>
  </p>
</form>
