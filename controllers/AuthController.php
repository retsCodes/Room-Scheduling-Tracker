<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

final class AuthController
{
    public function __construct(private User $users) {}

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('');
        }
        view('auth/login', ['title' => 'Log in']);
    }

    public function login(): void
    {
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('login');
        }
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $login !== '' ? $this->users->findByLogin($login) : null;
        if ($user === null || !$this->users->verifyPassword($user, $password)) {
            $_SESSION['_old'] = ['login' => $login];
            flash('error', 'Wrong username/email or password.');
            redirect('login');
        }
        unset($_SESSION['_old']);
        Auth::login($user);
        flash('success', 'Welcome back, ' . $user['username'] . '!');
        redirect('');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect('');
        }
        view('auth/register', ['title' => 'Create account']);
    }

    public function register(): void
    {
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('register');
        }
        $data = [
            'username'     => trim((string) ($_POST['username'] ?? '')),
            'email'        => trim((string) ($_POST['email'] ?? '')),
            'password'     => (string) ($_POST['password'] ?? ''),
            'role'         => in_array($_POST['role'] ?? '', User::ROLES, true) ? $_POST['role'] : 'student',
            'section'      => trim((string) ($_POST['section'] ?? '')) ?: null,
            'org_name'     => trim((string) ($_POST['org_name'] ?? '')) ?: null,
            'phone_number' => trim((string) ($_POST['phone_number'] ?? '')) ?: null,
        ];
        $_SESSION['_old'] = $data;
        $errors = $this->validateRegistration($data);
        if ($errors) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            redirect('register');
        }
        // The first account ever created becomes the admin, so a fresh
        // install is never locked out. Afterwards new accounts are students
        // unless an admin changes their role.
        if (count($this->users->all()) === 0) {
            $data['role'] = 'admin';
        }
        $id = $this->users->create($data);
        unset($_SESSION['_old']);
        $user = $this->users->find($id);
        Auth::login($user);
        flash('success', 'Account created. Welcome, ' . $data['username'] . '!');
        redirect('');
    }

    /** @return string[] */
    private function validateRegistration(array $data): array
    {
        $errors = [];
        if (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($this->users->findByUsername($data['username'])) {
            $errors[] = 'That username is already taken.';
        }
        if ($this->users->findByEmail($data['email'])) {
            $errors[] = 'That email is already registered.';
        }
        if ($data['phone_number'] && !preg_match('/^\+?[0-9][0-9 \-]{6,16}$/', $data['phone_number'])) {
            $errors[] = 'Phone number looks invalid (use PH format, e.g. +63917-123-4567).';
        }
        return $errors;
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'You have been logged out.');
        redirect('login');
    }
}
