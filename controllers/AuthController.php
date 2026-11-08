<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\User;

/**
 * Authentication.
 *
 * Admin and student logins are SEPARATE pages — they are not the same form:
 *   /login          — portal chooser (pick Admin or Student)
 *   /login/admin    — admins only (role must be admin)
 *   /login/student  — students (teachers log in here too)
 *   /register       — student registration (never creates an admin directly;
 *                     the first account on a fresh install becomes admin)
 */
final class AuthController
{
    public function __construct(private User $users) {}

    /** Portal chooser: two separate login pages. */
    public function showChooser(): void
    {
        if (Auth::check()) {
            redirect('');
        }
        view('auth/choose', ['title' => 'Log in']);
    }

    /**
     * @param 'admin'|'student' $scope Which login page this is.
     */
    public function showLogin(string $scope = 'student'): void
    {
        if (Auth::check()) {
            redirect('');
        }
        $scope = $scope === 'admin' ? 'admin' : 'student';
        view('auth/login', [
            'title' => $scope === 'admin' ? 'Admin log in' : 'Student log in',
            'scope' => $scope,
        ]);
    }

    /**
     * @param 'admin'|'student' $scope Which login page was submitted.
     */
    public function login(string $scope = 'student'): void
    {
        $scope = $scope === 'admin' ? 'admin' : 'student';
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request. Please try again.');
            redirect('login/' . $scope);
        }
        $login = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $login !== '' ? $this->users->findByLogin($login) : null;
        if ($user === null || !$this->users->verifyPassword($user, $password)) {
            $_SESSION['_old'] = ['login' => $login];
            flash('error', 'Wrong username/email or password.');
            redirect('login/' . $scope);
        }
        // Enforce the portal: an admin account cannot sign in through the
        // student page, and a student account cannot use the admin page.
        if ($scope === 'admin' && $user['role'] !== 'admin') {
            flash('error', 'That account is not an admin account. Use the student login instead.');
            redirect('login/student');
        }
        if ($scope === 'student' && !in_array($user['role'], ['student', 'teacher', 'scheduler', 'viewer'], true)) {
            flash('error', 'That is an admin account. Use the admin login instead.');
            redirect('login/admin');
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
        // Registration is for students/teachers only — nobody can
        // self-register as an admin.
        $role = in_array($_POST['role'] ?? '', ['student', 'teacher'], true)
            ? $_POST['role'] : 'student';
        $data = [
            'username'     => trim((string) ($_POST['username'] ?? '')),
            'email'        => trim((string) ($_POST['email'] ?? '')),
            'password'     => (string) ($_POST['password'] ?? ''),
            'role'         => $role,
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
