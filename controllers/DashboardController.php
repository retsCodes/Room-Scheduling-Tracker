<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Map;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleOccurrence;
use App\Services\AvailabilityService;

final class DashboardController
{
    public function __construct(
        private Database $db,
        private ScheduleOccurrence $occurrences,
        private Schedule $schedules,
        private Room $rooms,
        private Map $maps,
        private AvailabilityService $availability
    ) {}

    public function index(): void
    {
        Auth::requireLogin();
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $weekEnd = date('Y-m-d', strtotime('+6 days'));

        $stats = [
            'rooms'      => count($this->rooms->all()),
            'maps'       => count($this->maps->all()),
            'schedules'  => count($this->schedules->all(['status' => 'active'])),
            'today'      => count($this->occurrences->forDateRange($today, $today)),
        ];
        view('dashboard/index', [
            'title'       => 'Dashboard',
            'stats'       => $stats,
            'today'       => $this->occurrences->forDateRange($today, $today),
            'week'        => $this->occurrences->forDateRange($today, $weekEnd),
            'upcoming'    => $this->occurrences->upcoming(8),
            'room_status' => $this->availability->roomStatusAt($today, $now),
            'maps'        => $this->maps->all(true),
        ]);
    }
}
