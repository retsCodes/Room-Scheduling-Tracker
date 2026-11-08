<h1>Log in</h1>
<p class="muted">Choose your portal — admin and student logins are separate.</p>
<div class="grid-2">
  <section class="card">
    <h2>👩‍💼 Admin</h2>
    <p class="muted">Manage floor maps, rooms, schedules and users.</p>
    <a class="btn btn-primary" href="<?= url('login/admin') ?>">Admin log in →</a>
    <p class="muted">Demo: <code>admin</code> / <code>password123</code></p>
  </section>
  <section class="card">
    <h2>🎓 Student</h2>
    <p class="muted">Book rooms and view your schedules. Teachers log in here too.</p>
    <a class="btn btn-primary" href="<?= url('login/student') ?>">Student log in →</a>
    <p class="muted">Demo: <code>ana</code> / <code>password123</code></p>
  </section>
</div>
<p class="muted">No account yet? <a href="<?= url('register') ?>">Create a student account</a>.</p>
