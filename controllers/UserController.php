<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

final class UserController
{
    public function __construct(private User $users) {}

    public function index(): void
    {
        Auth::requireAdmin();
        view('users/index', ['title' => 'Users', 'users' => $this->users->all()]);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $user = $this->users->find($id);
        if ($user === null) {
            flash('error', 'User not found.');
            redirect('users');
        }
        view('users/form', ['title' => 'Edit user', 'user' => $user]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('users');
        }
        $data = [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'role' => in_array($_POST['role'] ?? '', User::ROLES, true) ? $_POST['role'] : 'student',
            'section' => trim((string) ($_POST['section'] ?? '')) ?: null,
            'org_name' => trim((string) ($_POST['org_name'] ?? '')) ?: null,
            'phone_number' => trim((string) ($_POST['phone_number'] ?? '')) ?: null,
        ];
        if ($data['username'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Username and a valid email are required.');
            redirect('users/' . $id . '/edit');
        }
        $this->users->update($id, $data);
        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                flash('error', 'New password must be at least 8 characters.');
                redirect('users/' . $id . '/edit');
            }
            $this->users->updatePassword($id, $newPassword);
        }
        flash('success', 'User updated.');
        redirect('users');
    }

    public function destroy(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('users');
        }
        if ($id === Auth::id()) {
            flash('error', 'You cannot delete your own account.');
            redirect('users');
        }
        try {
            $this->users->delete($id);
            flash('success', 'User deleted.');
        } catch (\Throwable $e) {
            flash('error', 'Cannot delete this user - they still own schedules.');
        }
        redirect('users');
    }
}
