<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Map;
use App\Models\Room;
use App\Services\AvailabilityService;

final class AvailabilityController
{
    public function __construct(
        private AvailabilityService $availability,
        private Room $rooms,
        private Map $maps
    ) {}

    public function index(): void
    {
        Auth::requireLogin();
        $date = $_GET['date'] ?? date('Y-m-d');
        $start = $_GET['start_time'] ?? '09:00';
        $end = $_GET['end_time'] ?? '10:00';
        $mapId = isset($_GET['map_id']) && $_GET['map_id'] !== '' ? (int) $_GET['map_id'] : null;
        $searched = isset($_GET['date']);
        $rooms = [];
        if ($searched && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && $end > $start) {
            $occ = $this->availability->occurrences();
            $byRoom = [];
            foreach ($occ->forDateRange($date, $date) as $o) {
                if ($o['occurrence_status'] !== 'scheduled') continue;
                if ($o['start_time'] < $end . ':00' && $o['end_time'] > $start . ':00') {
                    $byRoom[(int) $o['room_id']][] = $o;
                }
            }
            foreach ($this->rooms->all($mapId) as $r) {
                $rooms[] = [
                    'room' => $r,
                    'free' => empty($byRoom[(int) $r['room_id']]),
                    'bookings' => $byRoom[(int) $r['room_id']] ?? [],
                ];
            }
        }
        view('availability/index', [
            'title' => 'Check availability',
            'date' => $date, 'start' => $start, 'end' => $end, 'map_id' => $mapId,
            'rooms' => $rooms, 'searched' => $searched,
            'maps' => $this->maps->all(true),
        ]);
    }
}
