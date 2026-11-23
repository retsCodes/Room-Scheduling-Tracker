<?php
declare(strict_types=1);

// Vercel entry point. The vercel-php runtime serves this file for every
// request that has no matching static file (see vercel.json). Forward into
// the app's front controller, pretending the script lives at the web root
// so base_path() resolves to ''.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__) . '/index.php';

require dirname(__DIR__) . '/index.php';
