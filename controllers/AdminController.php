<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Map;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use App\Models\User;
use App\Services\AvailabilityService;


final class AdminController
{
    public function __construct(
        private User $users,
        private Room $rooms,
        private Map $maps,
        private Schedule $schedules,
        private ScheduleOccurrence $occurrences,
        private AvailabilityService $availability
    ) {}

    public function index(): void
    {
        Auth::requireAdmin();
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $status = $this->availability->roomStatusAt($today, $now);
        $freeNow = count(array_filter($status, fn ($s) => $s['free']));
        $unconfirmed = $this->schedules->all(['unconfirmed' => true]);
        $allUsers = $this->users->all();
        view('admin/index', [
            'title' => 'Admin dashboard',
            'layout' => 'admin',
            'stats' => [
                'users' => count($allUsers),
                'rooms' => count($this->rooms->all()),
                'maps' => count($this->maps->all()),
                'unconfirmed' => count($unconfirmed),
                'today' => count($this->occurrences->forDateRange($today, $today)),
                'free_now' => $freeNow,
                'busy_now' => count($status) - $freeNow,
            ],
            'unconfirmed' => array_slice($unconfirmed, 0, 10),
            'users' => array_slice($allUsers, 0, 5),
        ]);
    }
}
