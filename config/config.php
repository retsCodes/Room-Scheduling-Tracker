<?php
declare(strict_types=1);

/**
 * Application configuration.
 *
 * Values come from environment variables, with sensible local defaults.
 * Copy `.env.example` to `.env` and edit it — never commit real credentials.
 * A tiny .env loader is included so the app works without a web-server
 * env setup (XAMPP / Laragon / plain `php -S`).
 *
 * No folder name is configured anywhere: links, redirects and assets use
 * base_path() (core/helpers.php), which detects the project folder from
 * the running script at runtime. Rename the XAMPP folder freely.
 * Setting APP_URL (e.g. APP_URL=http://localhost/MyFolder) overrides
 * the detection if you ever need it to.
 */

$root = dirname(__DIR__);

// --- Minimal .env loader (KEY=VALUE lines, # comments, no quotes magic) ---
$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $k = trim($k);
        $v = trim(trim($v), "\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
}

function env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return $v === false || $v === '' ? $default : $v;
}

// A relative DB_PATH in .env is resolved against the project root, not the
// web server's working directory.
$dbPath = env('DB_PATH', $root . '/storage/rst.db');
if (!str_starts_with($dbPath, '/') && !preg_match('/^[A-Za-z]:\\\\/', $dbPath)) {
    $dbPath = $root . '/' . $dbPath;
}

return [
    'app_name' => env('APP_NAME', 'Room Scheduling Tracker'),
    'app_url'  => env('APP_URL', 'http://localhost:8000'),
    'debug'    => env('APP_DEBUG', 'false') === 'true',

    // Database. driver: mysql (default, matches RST_DB.xlsx) or sqlite
    // (handy for a quick local demo: DB_DRIVER=sqlite DB_PATH=storage/rst.db).
    'db' => [
        'driver'   => env('DB_DRIVER', 'mysql'),
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', '3306'),
        'database' => env('DB_NAME', 'rst_db'),
        'username' => env('DB_USER', 'root'),
        'password' => env('DB_PASS', ''),
        'path'     => $dbPath,
        'charset'  => 'utf8mb4',
    ],

    // Uploads live under the project root (outside the web-served views).
    'uploads' => [
        'profiles' => $root . '/uploads/profiles',
        'maps'     => $root . '/uploads/maps',
    ],
];
