<?php
declare(strict_types=1);



$root = dirname(__DIR__);


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



$dbPath = env('DB_PATH', $root . '/storage/rst.db');
if (!str_starts_with($dbPath, '/') && !preg_match('/^[A-Za-z]:\\\\/', $dbPath)) {
    $dbPath = $root . '/' . $dbPath;
}

return [
    'app_name' => env('APP_NAME', 'Room Scheduling Tracker'),
    'app_url'  => env('APP_URL', 'http://localhost:8000'),
    'debug'    => env('APP_DEBUG', 'false') === 'true',

    
    
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

    
    'uploads' => [
        'profiles' => $root . '/uploads/profiles',
        'maps'     => $root . '/uploads/maps',
    ],
];
