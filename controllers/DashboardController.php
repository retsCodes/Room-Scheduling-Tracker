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

/**
 * Home page — public. The first thing anyone sees is the FIRST FLOOR
 * layout (the lowest active floor map) with live room status, not a
 * login wall. Logged-in users get the same map plus their dashboard
 * (stats, today's bookings, upcoming). Students only view; booking
 * controls are gated by Auth::canBook() in the views.
 */
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
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $weekEnd = date('Y-m-d', strtotime('+6 days'));

        // First floor = lowest floor_number among active maps.
        $activeMaps = $this->maps->all(true);
        $firstMap = $activeMaps[0] ?? null;
        $firstRooms = $firstMap ? $this->rooms->all((int) $firstMap['map_id']) : [];
        $busy = [];
        if ($firstMap) {
            foreach ($this->occurrences->forDateRange($today, $today) as $occ) {
                if ($occ['start_time'] <= $now && $occ['end_time'] > $now
                    && $occ['occurrence_status'] === 'scheduled') {
                    $busy[(int) $occ['room_id']] = $occ;
                }
            }
        }
        $firstStatus = $this->availability->roomStatusAt($today, $now);
        $freeNow = count(array_filter($firstStatus, fn ($s) => $s['free']));
        $busyNow = count($firstStatus) - $freeNow;

        $stats = [
            'rooms'      => count($this->rooms->all()),
            'maps'       => count($this->maps->all()),
            'schedules'  => count($this->schedules->all(['status' => 'active'])),
            'today'      => count($this->occurrences->forDateRange($today, $today)),
            'free_now'   => $freeNow,
            'busy_now'   => $busyNow,
        ];
        view('dashboard/index', [
            'title'       => Auth::check() ? 'Dashboard' : 'Campus Map',
            'stats'       => $stats,
            'today'       => $this->occurrences->forDateRange($today, $today),
            'week'        => $this->occurrences->forDateRange($today, $weekEnd),
            'upcoming'    => $this->occurrences->upcoming(8),
            'room_status' => $firstStatus,
            'maps'        => $activeMaps,
            'first_map'   => $firstMap,
            'first_rooms' => $firstRooms,
            'busy'        => $busy,
            'today_date'  => $today,
            'extra_js'    => 'assets/js/map.js',
        ]);
    }
}
