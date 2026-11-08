<h1>Log in</h1>
<p class="muted">Use your username or email. Demo account: <code>admin</code> / <code>password123</code> (after importing <code>database/seed.sql</code>).</p>
<form method="post" action="<?= url('login') ?>" class="card form">
  <?= csrf_field() ?>
  <label>Username or email
    <input type="text" name="login" required value="<?= old('login') ?>" autofocus>
  </label>
  <label>Password
    <input type="password" name="password" required>
  </label>
  <button class="btn btn-primary" type="submit">Log in</button>
  <p class="muted">No account yet? <a href="<?= url('register') ?>">Create one</a>.</p>
</form>
