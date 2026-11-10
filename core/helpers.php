<?php
declare(strict_types=1);



function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


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


function day_name(int $day): string
{
    return [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'][$day] ?? '';
}

function time_12h(string $time): string
{
    $ts = strtotime($time);
    return $ts === false ? $time : date('g:i A', $ts);
}


function ordinal(int $n): string
{
    $suffix = 'th';
    if (($n % 100 < 11 || $n % 100 > 13)) {
        $suffix = [1 => 'st', 2 => 'nd', 3 => 'rd'][$n % 10] ?? 'th';
    }
    return $n . $suffix;
}

function confirm_badge($confirmed): string
{
    return ((int) $confirmed === 1)
        ? '<span class="badge badge-green">✓ Admin confirmed</span>'
        : '<span class="badge badge-amber">Awaiting confirmation</span>';
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
