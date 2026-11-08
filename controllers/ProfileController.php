<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

/**
 * My Profile (Figma: User Profile / Admin Profile) — avatar with
 * Change Picture, editable details, password change, delete account.
 * A user can never change their own role here; only an admin can do that
 * on the User List page.
 */
final class ProfileController
{
    private const AVATAR_DIR = 'uploads/profiles';
    private const AVATAR_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

    public function __construct(private User $users) {}

    public function show(): void
    {
        Auth::requireLogin();
        $user = $this->users->find((int) Auth::id());
        if ($user === null) {
            Auth::logout();
            redirect('login');
        }
        view('profile/show', ['title' => 'My Profile', 'user' => $user]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('profile');
        }
        $id = (int) Auth::id();
        $data = [
            'username'     => trim((string) ($_POST['username'] ?? '')),
            'email'        => trim((string) ($_POST['email'] ?? '')),
            'section'      => trim((string) ($_POST['section'] ?? '')) ?: null,
            'org_name'     => trim((string) ($_POST['org_name'] ?? '')) ?: null,
            'phone_number' => trim((string) ($_POST['phone_number'] ?? '')) ?: null,
        ];
        $errors = [];
        if (strlen($data['username']) < 3) $errors[] = 'Username must be at least 3 characters.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
        $byName = $this->users->findByUsername($data['username']);
        if ($byName && (int) $byName['user_id'] !== $id) $errors[] = 'That username is already taken.';
        $byEmail = $this->users->findByEmail($data['email']);
        if ($byEmail && (int) $byEmail['user_id'] !== $id) $errors[] = 'That email is already registered.';
        if ($errors) {
            foreach ($errors as $e) flash('error', $e);
            redirect('profile');
        }
        $this->users->updateProfile($id, $data);
        $user = $this->users->find($id);
        $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $user);
        flash('success', 'Profile updated.');
        redirect('profile');
    }

    public function changePassword(): void
    {
        Auth::requireLogin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('profile');
        }
        $id = (int) Auth::id();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $row = $this->users->findByLogin((string) Auth::user()['username']);
        if ($row === null || !$this->users->verifyPassword($row, $current)) {
            flash('error', 'Your current password is wrong.');
            redirect('profile');
        }
        if (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            redirect('profile');
        }
        $this->users->updatePassword($id, $new);
        flash('success', 'Password changed.');
        redirect('profile');
    }

    public function uploadAvatar(): void
    {
        Auth::requireLogin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('profile');
        }
        $id = (int) Auth::id();
        $file = $_FILES['profile_img'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Choose a picture first.');
            redirect('profile');
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            flash('error', 'Picture must be under 2 MB.');
            redirect('profile');
        }
        $info = getimagesize($file['tmp_name']);
        $ext = $info ? (self::AVATAR_TYPES[$info['mime']] ?? null) : null;
        if ($ext === null) {
            flash('error', 'Only JPG, PNG, GIF or WebP pictures are allowed.');
            redirect('profile');
        }
        $dir = dirname(__DIR__) . '/' . self::AVATAR_DIR;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = 'user_' . $id . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            flash('error', 'Could not save the picture.');
            redirect('profile');
        }
        $this->users->updateProfileImg($id, self::AVATAR_DIR . '/' . $name);
        $user = $this->users->find($id);
        $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], $user);
        flash('success', 'Profile picture updated.');
        redirect('profile');
    }

    public function deleteAccount(): void
    {
        Auth::requireLogin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('profile');
        }
        $this->users->delete((int) Auth::id());
        Auth::logout();
        flash('success', 'Your account has been deleted.');
        redirect('login');
    }
}
