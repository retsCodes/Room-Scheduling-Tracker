<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">R</div>
    <h1>Welcome Back!</h1>
    <p class="muted">
      Log in to check room schedules and see what's free right now.
      <br><span class="muted">Demo: <code>ana</code> / <code>password123</code> · admin: <code>admin</code> / <code>password123</code></span>
    </p>
    <form method="post" action="<?= url('login') ?>" class="form">
      <?= csrf_field() ?>
      <label>Username or email
        <input type="text" name="login" required value="<?= old('login') ?>" autofocus>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button class="btn btn-primary" type="submit">Log in →</button>
    </form>
    <p class="muted" style="margin-top:1rem">
      No account yet? <a href="<?= url('register') ?>">Create a student account</a>
    </p>
  </div>
</div>
