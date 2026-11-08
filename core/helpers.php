<?php
declare(strict_types=1);

/** Shared view / request helpers. Loaded by index.php at the project root. */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * The URL path of the folder this project lives in, detected at runtime
 * so the app works no matter what the folder is called.
 *
 * Examples:
 *   http://localhost/index.php            -> ''        (project at web root)
 *   http://localhost/Room-Scheduling-Tracker/index.php
 *                                          -> '/Room-Scheduling-Tracker'
 *   http://localhost/my-school-app/index.php
 *                                          -> '/my-school-app'
 *
 * Rename the XAMPP folder freely — no code or config change is needed.
 * An APP_URL with a path (e.g. APP_URL=http://localhost/MyFolder)
 * overrides the detection.
 */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $base = '';
    $appUrl = getenv('APP_URL');
    if ($appUrl !== false && $appUrl !== '') {
        $p = parse_url($appUrl, PHP_URL_PATH);
        if (is_string($p) && $p !== '') {
            $base = rtrim($p, '/');
        }
    } elseif (isset($_SERVER['SCRIPT_NAME'])) {
        $script = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
        if (basename($script) === 'index.php') {
            $dir = rtrim(dirname($script), '/');
            $base = ($dir === '/' || $dir === '.' || $dir === '\\') ? '' : $dir;
        }
    }
    return $base;
}

/**
 * Build a URL inside the app, prefixed with the detected base path.
 * Use this for EVERY link, form action, redirect and asset — never write
 * a leading '/' by hand, or it will break when the project lives in a
 * subfolder (the usual XAMPP setup).
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return base_path() . ($path === '' ? '/' : '/' . $path);
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
