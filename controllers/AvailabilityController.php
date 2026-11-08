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
        $free = [];
        $searched = isset($_GET['date']);
        if ($searched && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && $end > $start) {
            $free = $this->availability->freeRooms($date, $start . ':00', $end . ':00', $mapId);
        }
        view('availability/index', [
            'title' => 'Check availability',
            'date' => $date, 'start' => $start, 'end' => $end, 'map_id' => $mapId,
            'free' => $free, 'searched' => $searched,
            'maps' => $this->maps->all(true),
            'rooms' => $this->rooms->all($mapId),
        ]);
    }
}
