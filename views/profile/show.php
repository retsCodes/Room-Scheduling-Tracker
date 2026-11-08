<h1><?= \App\Core\Auth::isAdmin() ? 'Admin Profile' : 'User Profile' ?></h1>
<p class="muted">Your account details — only an admin can change your role.</p>

<div class="grid-2">
  <section class="card profile-side">
    <div class="avatar">
      <?php if (!empty($user['profile_img'])): ?>
        <img src="<?= url($user['profile_img']) ?>" alt="Profile picture">
      <?php else: ?>
        <span class="avatar-silhouette">👤</span>
      <?php endif; ?>
    </div>
    <form method="post" action="<?= url('profile/avatar') ?>" enctype="multipart/form-data" class="form">
      <?= csrf_field() ?>
      <label class="btn" for="avatar-file" style="cursor:pointer">Change Picture</label>
      <input type="file" id="avatar-file" name="profile_img" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="this.form.submit()">
    </form>
    <p class="muted">JPG, PNG, GIF or WebP, under 2 MB.</p>
    <form method="post" action="<?= url('profile/delete') ?>" onsubmit="return confirm('Delete your account? This cannot be undone.')">
      <?= csrf_field() ?><button class="btn btn-danger" type="submit">Delete Account</button>
    </form>
  </section>

  <section class="card">
    <form method="post" action="<?= url('profile') ?>" class="form">
      <?= csrf_field() ?>
      <label>Username <input type="text" name="username" required minlength="3" value="<?= e($user['username']) ?>"></label>
      <label>Email <input type="email" name="email" required value="<?= e($user['email']) ?>"></label>
      <label>Role <input type="text" value="<?= e($user['role']) ?><?= $user['role'] === 'officer' ? ' (org officer)' : '' ?>" disabled></label>
      <label>Section <input type="text" name="section" value="<?= e($user['section'] ?? '') ?>" placeholder="BSIT-1A"></label>
      <label>Organization name <input type="text" name="org_name" value="<?= e($user['org_name'] ?? '') ?>" placeholder="Computer Science Society"></label>
      <label>Phone number <input type="tel" name="phone_number" value="<?= e($user['phone_number'] ?? '') ?>" placeholder="+63917-123-4567"></label>
      <button class="btn btn-primary" type="submit">Save changes</button>
    </form>
  </section>
</div>

<section class="card">
  <h2>Change password</h2>
  <form method="post" action="<?= url('profile/password') ?>" class="form">
    <?= csrf_field() ?>
    <label>Current password <input type="password" name="current_password" required></label>
    <label>New password (min. 8 characters) <input type="password" name="new_password" required minlength="8"></label>
    <button class="btn btn-primary" type="submit">Change password</button>
  </form>
</section>
