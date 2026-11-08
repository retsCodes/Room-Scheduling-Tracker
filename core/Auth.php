<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Session authentication + role checks.
 * Roles come from tblUsers.role: admin, teacher, student (the spreadsheet
 * also suggests scheduler / viewer — those are accepted as roles too and
 * behave like teacher / viewer respectively).
 */
final class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    /** Admins and teachers/schedulers can create and manage schedules. */
    public static function canBook(): bool
    {
        return in_array(self::role(), ['admin', 'teacher', 'scheduler', 'student'], true);
    }

    /** Only admins manage maps, rooms and users. */
    public static function canManage(): bool
    {
        return self::isAdmin();
    }

    public static function login(array $userRow): void
    {
        session_regenerate_id(true);
        unset($userRow['password_hash']);
        $_SESSION['user'] = $userRow;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Please log in first.');
            redirect('login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::canManage()) {
            flash('error', 'Only an admin can do that.');
            redirect('');
        }
    }

    public static function requireCanBook(): void
    {
        self::requireLogin();
        if (!self::canBook()) {
            flash('error', 'Your role cannot book rooms.');
            redirect('');
        }
    }
}
