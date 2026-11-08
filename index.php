<?php
declare(strict_types=1);

/**
 * Room Scheduling Tracker — front controller (web PHP, OOP, no app/ folder).
 * Run: php -S localhost:8000
 * Or drop the project folder into htdocs and open
 * http://localhost/Room-Scheduling-Tracker
 */

session_start();

require __DIR__ . '/core/helpers.php';

// Class map for the App\ namespaces — no app/ folder, no composer needed:
//   App\Core\...       -> core/...
//   App\Models\...     -> models/...
//   App\Services\...   -> services/...
//   App\Controllers\...-> controllers/...
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $map = [
        'Core\\' => 'core/',
        'Models\\' => 'models/',
        'Services\\' => 'services/',
        'Controllers\\' => 'controllers/',
    ];
    foreach ($map as $ns => $dir) {
        if (str_starts_with($relative, $ns)) {
            $file = __DIR__ . '/' . $dir . str_replace('\\', '/', substr($relative, strlen($ns))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

use App\Controllers\AuthController;
use App\Controllers\AvailabilityController;
use App\Controllers\DashboardController;
use App\Controllers\MapController;
use App\Controllers\OccurrenceController;
use App\Controllers\ProfileController;
use App\Controllers\RoomController;
use App\Controllers\ScheduleController;
use App\Controllers\UserController;
use App\Core\Database;
use App\Models\Map;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Models\ScheduleOccurrence;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\RecurrenceService;

$config = require __DIR__ . '/config/config.php';

/** Render a view inside the main layout. */
function view(string $name, array $data = []): void
{
    extract($data);
    $viewFile = __DIR__ . '/views/' . $name . '.php';
    if (!is_file($viewFile)) {
        http_response_code(500);
        echo 'View not found: ' . htmlspecialchars($name);
        return;
    }
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require __DIR__ . '/views/layouts/main.php';
}

// --- Wiring (plain constructor injection; no framework needed) --------------
$db          = Database::getInstance($config);
$users       = new User($db);
$maps        = new Map($db);
$rooms       = new Room($db);
$schedules   = new Schedule($db);
$occurrences = new ScheduleOccurrence($db);
$exceptions  = new ScheduleException($db);
$recurrence  = new RecurrenceService($occurrences, $exceptions);
$availability = new AvailabilityService($occurrences, $rooms);

$auth        = new AuthController($users);
$dashboard   = new DashboardController($db, $occurrences, $schedules, $rooms, $maps, $availability);
$mapCtl      = new MapController($maps, $rooms, $occurrences);
$roomCtl     = new RoomController($rooms, $maps, $occurrences);
$scheduleCtl = new ScheduleController($db, $schedules, $occurrences, $exceptions, $rooms, $recurrence, $availability);
$occCtl      = new OccurrenceController($occurrences);
$availCtl    = new AvailabilityController($availability, $rooms, $maps);
$userCtl     = new UserController($users);
$profileCtl  = new ProfileController($users);

// --- Routing -----------------------------------------------------------------
// $path is the URL path inside the app (e.g. "schedules/25/edit").
// The project folder name is stripped first, so routing works whether the
// app lives at the web root or in any subfolder (XAMPP: htdocs/<any-name>/).
// POST forms use a hidden _method-free convention: the same URL handles the
// POST (update/store/delete) when the request method is POST.
$uri  = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/';
$base = base_path();
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
    $uri = $uri === '' ? '/' : $uri;
}
$path = trim($uri, '/');
// Strip a leading "index.php" when the server exposes it in the URL
// (e.g. /Room-Scheduling-Tracker/index.php/schedules).
$path = preg_replace('#^index\.php/?#', '', $path) ?? $path;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isPost = $method === 'POST';

/** @param array<int, string> $segments */
$match = function (array $segments, string $pattern): ?array {
    // Pattern like "schedules/{id}/edit" — returns ['id' => '25'] or null.
    $parts = explode('/', trim($pattern, '/'));
    if (count($parts) !== count($segments)) {
        return null;
    }
    $params = [];
    foreach ($parts as $i => $part) {
        if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
            $params[trim($part, '{}')] = $segments[$i];
        } elseif ($part !== $segments[$i]) {
            return null;
        }
    }
    return $params;
};

$segments = $path === '' ? [] : explode('/', $path);

try {
    if ($path === '') {
        $dashboard->index();
    } elseif ($path === 'login' && !$isPost) {
        // The public login page is the STUDENT login only. The admin login
        // lives at /login/admin and is deliberately not linked from any
        // student-facing page — admins reach it by direct URL.
        $auth->showLogin('student');
    } elseif ($path === 'login/admin' && !$isPost) {
        $auth->showLogin('admin');
    } elseif ($path === 'login/admin' && $isPost) {
        $auth->login('admin');
    } elseif ($path === 'login/student' && !$isPost) {
        $auth->showLogin('student');
    } elseif ($path === 'login/student' && $isPost) {
        $auth->login('student');
    } elseif ($path === 'register' && !$isPost) {
        $auth->showRegister();
    } elseif ($path === 'register' && $isPost) {
        $auth->register();
    } elseif ($path === 'logout' && $isPost) {
        $auth->logout();
    } elseif ($path === 'profile' && !$isPost) {
        $profileCtl->show();
    } elseif ($path === 'profile' && $isPost) {
        $profileCtl->update();
    } elseif ($path === 'profile/password' && $isPost) {
        $profileCtl->changePassword();
    } elseif ($path === 'profile/avatar' && $isPost) {
        $profileCtl->uploadAvatar();
    } elseif ($path === 'profile/delete' && $isPost) {
        $profileCtl->deleteAccount();
    } elseif ($path === 'maps' && !$isPost) {
        $mapCtl->index();
    } elseif ($path === 'maps' && $isPost) {
        $mapCtl->store();
    } elseif ($path === 'maps/create' && !$isPost) {
        $mapCtl->create();
    } elseif (($p = $match($segments, 'maps/{id}')) && !$isPost) {
        $mapCtl->show((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}')) && $isPost) {
        $mapCtl->update((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}/edit')) && !$isPost) {
        $mapCtl->edit((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}/delete')) && $isPost) {
        $mapCtl->destroy((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}/editor')) && !$isPost) {
        $mapCtl->editor((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}/layout')) && $isPost) {
        $mapCtl->saveLayout((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}/export')) && !$isPost) {
        $mapCtl->exportMap((int) $p['id']);
    } elseif (($p = $match($segments, 'maps/{id}/import')) && $isPost) {
        $mapCtl->importMap((int) $p['id']);
    } elseif ($path === 'rooms' && !$isPost) {
        $roomCtl->index();
    } elseif ($path === 'rooms' && $isPost) {
        $roomCtl->store();
    } elseif ($path === 'rooms/create' && !$isPost) {
        $roomCtl->create();
    } elseif (($p = $match($segments, 'rooms/{id}')) && !$isPost) {
        $roomCtl->show((int) $p['id']);
    } elseif (($p = $match($segments, 'rooms/{id}')) && $isPost) {
        $roomCtl->update((int) $p['id']);
    } elseif (($p = $match($segments, 'rooms/{id}/edit')) && !$isPost) {
        $roomCtl->edit((int) $p['id']);
    } elseif (($p = $match($segments, 'rooms/{id}/delete')) && $isPost) {
        $roomCtl->destroy((int) $p['id']);
    } elseif ($path === 'schedules' && !$isPost) {
        $scheduleCtl->index();
    } elseif ($path === 'schedules' && $isPost) {
        $scheduleCtl->store();
    } elseif ($path === 'schedules/create' && !$isPost) {
        $scheduleCtl->create();
    } elseif ($path === 'calendar' && !$isPost) {
        $scheduleCtl->calendar();
    } elseif (($p = $match($segments, 'schedules/{id}')) && !$isPost) {
        $scheduleCtl->show((int) $p['id']);
    } elseif (($p = $match($segments, 'schedules/{id}')) && $isPost) {
        $scheduleCtl->update((int) $p['id']);
    } elseif (($p = $match($segments, 'schedules/{id}/edit')) && !$isPost) {
        $scheduleCtl->edit((int) $p['id']);
    } elseif (($p = $match($segments, 'schedules/{id}/delete')) && $isPost) {
        $scheduleCtl->destroy((int) $p['id']);
    } elseif (($p = $match($segments, 'schedules/{id}/cancel')) && $isPost) {
        $scheduleCtl->cancel((int) $p['id']);
    } elseif (($p = $match($segments, 'schedules/{id}/exceptions')) && $isPost) {
        $scheduleCtl->storeException((int) $p['id']);
    } elseif ($path === 'occurrences' && !$isPost) {
        $occCtl->index();
    } elseif (($p = $match($segments, 'occurrences/{id}/status')) && $isPost) {
        $occCtl->setStatus((int) $p['id']);
    } elseif ($path === 'availability' && !$isPost) {
        $availCtl->index();
    } elseif ($path === 'users' && !$isPost) {
        $userCtl->index();
    } elseif (($p = $match($segments, 'users/{id}/edit')) && !$isPost) {
        $userCtl->edit((int) $p['id']);
    } elseif (($p = $match($segments, 'users/{id}')) && $isPost) {
        $userCtl->update((int) $p['id']);
    } elseif (($p = $match($segments, 'users/{id}/delete')) && $isPost) {
        $userCtl->destroy((int) $p['id']);
    } else {
        http_response_code(404);
        view('dashboard/index', [
            'title' => 'Not found',
            'stats' => ['rooms' => 0, 'maps' => 0, 'schedules' => 0, 'today' => 0, 'free_now' => 0, 'busy_now' => 0],
            'today' => [], 'week' => [], 'upcoming' => [], 'room_status' => [], 'maps' => [],
            'first_map' => null, 'first_rooms' => [], 'busy' => [], 'today_date' => date('Y-m-d'),
        ]);
        echo '<p class="flash flash-error">Page not found: ' . htmlspecialchars($path) . '</p>';
    }
} catch (\Throwable $e) {
    if (($config['debug'] ?? false) === true) {
        throw $e;
    }
    http_response_code(500);
    echo '<h1>Something went wrong</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>'
        . '<p><a href="' . e(url('')) . '">Back to dashboard</a></p>';
}
