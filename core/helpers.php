<?php
declare(strict_types=1);

/** Shared view / request helpers. Loaded by index.php at the project root. */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    // index.php lives at the project root, so a plain root-relative path
    // is correct in the normal setup.
    return '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function old(string $key, string $default = ''): string
{
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int, array{type: string, message: string}> */
function flashes(): array
{
    $out = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $out;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    return $sent !== '' && hash_equals((string) ($_SESSION['_csrf'] ?? ''), $sent);
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Day number (1=Mon..7=Sun) to short name, per the spreadsheet. */
function day_name(int $day): string
{
    return [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'][$day] ?? '';
}

function time_12h(string $time): string
{
    $ts = strtotime($time);
    return $ts === false ? $time : date('g:i A', $ts);
}

function status_badge(string $status): string
{
    $map = [
        'active' => 'green', 'scheduled' => 'green',
        'cancelled' => 'red', 'archived' => 'gray', 'completed' => 'blue',
        'unavailable' => 'red', 'skipped' => 'amber', 'moved' => 'blue',
        'modified' => 'blue',
    ];
    $class = $map[strtolower($status)] ?? 'gray';
    return '<span class="badge badge-' . $class . '">' . e(ucfirst($status)) . '</span>';
}
