<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">R</div>
    <h1>Create an Account</h1>
    <p class="muted">The very first account on a fresh install becomes the admin.</p>
    <form method="post" action="<?= url('register') ?>" class="form">
      <?= csrf_field() ?>
      <label>Username
        <input type="text" name="username" required minlength="3" value="<?= old('username') ?>">
      </label>
      <label>Email
        <input type="email" name="email" required value="<?= old('email') ?>">
      </label>
      <label>Password (min. 8 characters)
        <input type="password" name="password" required minlength="8">
      </label>
      <label>Role
        <select name="role">
          <option value="student">Student</option>
          <option value="teacher">Teacher</option>
        </select>
      </label>
      <p class="muted">Org officers are set by an admin on the Users page - you can't register as one.</p>
      <label>Section (optional, for students)
        <input type="text" name="section" placeholder="BSIT-1A" value="<?= old('section') ?>">
      </label>
      <label>Organization name (optional)
        <input type="text" name="org_name" placeholder="Computer Science Society" value="<?= old('org_name') ?>">
      </label>
      <label>Phone number (optional, PH format)
        <input type="tel" name="phone_number" placeholder="+63917-123-4567" value="<?= old('phone_number') ?>">
      </label>
      <button class="btn btn-primary" type="submit">Create account →</button>
    </form>
    <p class="muted" style="margin-top:1rem">Already have an account? <a href="<?= url('login') ?>">Log in</a>.</p>
  </div>
</div>
