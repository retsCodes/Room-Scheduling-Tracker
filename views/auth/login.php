<?php $isAdmin = ($scope ?? 'student') === 'admin'; ?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">R</div>
    <h1><?= $isAdmin ? 'Admin log in' : 'Welcome Back!' ?></h1>
    <p class="muted">
      <?= $isAdmin
        ? 'For administrators only - manage maps, rooms, schedules and users.'
        : 'Log in to check room schedules and see what\'s free right now.' ?>
      <?php if ($isAdmin): ?>
        <br><span class="muted">Demo: <code>admin</code> / <code>password123</code></span>
      <?php else: ?>
        <br><span class="muted">Demo: <code>ana</code> / <code>password123</code></span>
      <?php endif; ?>
    </p>
    <form method="post" action="<?= url('login/' . ($isAdmin ? 'admin' : 'student')) ?>" class="form">
      <?= csrf_field() ?>
      <label>Username or email
        <input type="text" name="login" required value="<?= old('login') ?>" autofocus>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button class="btn btn-primary" type="submit">Log in<?= $isAdmin ? ' as admin' : '' ?> →</button>
    </form>
    <p class="muted" style="margin-top:1rem">
      <?php if ($isAdmin): ?>
        <a href="<?= url('') ?>">← Back to the campus map</a>
      <?php else: ?>
        No account yet? <a href="<?= url('register') ?>">Create a student account</a>
      <?php endif; ?>
    </p>
  </div>
</div>
