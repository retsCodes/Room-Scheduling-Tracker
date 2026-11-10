<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Map;
use App\Models\Room;
use App\Models\ScheduleOccurrence;

final class RoomController
{
    public function __construct(
        private Room $rooms,
        private Map $maps,
        private ScheduleOccurrence $occurrences
    ) {}

    
    public function index(): void
    {
        Auth::requireLogin();
        if (isset($_GET['recent']) && $_GET['recent'] === '1' && !empty($_SESSION['room_filters'])) {
            $f = $_SESSION['room_filters'];
            $_GET = array_merge($_GET, $f);
        }
        $today = date('Y-m-d');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? '')) ? $_GET['date'] : $today;
        $time = preg_match('/^\d{2}:\d{2}$/', (string) ($_GET['time'] ?? '')) ? $_GET['time'] : ($date === $today ? date('H:i') : '08:00');
        $search = trim((string) ($_GET['search'] ?? ''));
        $mapId = isset($_GET['map_id']) && $_GET['map_id'] !== '' ? (int) $_GET['map_id'] : null;
        $statusFilter = in_array($_GET['status'] ?? '', ['available', 'occupied', 'reserved', 'unavailable'], true) ? $_GET['status'] : '';
        $organizer = trim((string) ($_GET['organizer'] ?? ''));
        $_SESSION['room_filters'] = [
            'date' => $date, 'time' => $time, 'search' => $search,
            'map_id' => $mapId ?? '', 'status' => $statusFilter, 'organizer' => $organizer,
        ];

        
        $weekStart = date('Y-m-d', strtotime($date . ' -' . date('w', strtotime($date)) . ' days'));
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $d = date('Y-m-d', strtotime($weekStart . " +$i days"));
            $week[] = ['date' => $d, 'day' => (int) date('j', strtotime($d)), 'name' => date('D', strtotime($d))];
        }
        $prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
        $nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));

        
        $byRoom = [];
        foreach ($this->occurrences->forDateRange($date, $date) as $occ) {
            $byRoom[(int) $occ['room_id']][] = $occ;
        }
        $timeSec = $time . ':00';
        $grouped = [];
        $total = 0;
        foreach ($this->rooms->all($mapId) as $room) {
            $rid = (int) $room['room_id'];
            if ($search !== '' && stripos($room['room_name'], $search) === false) continue;
            $occ = $byRoom[$rid] ?? [];
            $current = null; $next = null;
            foreach ($occ as $o) {
                if ($o['occurrence_status'] !== 'scheduled') continue;
                if ($o['start_time'] <= $timeSec && $o['end_time'] > $timeSec) $current = $o;
                elseif ($o['start_time'] > $timeSec && $next === null) $next = $o;
            }
            if (($room['room_status'] ?? 'active') !== 'active') {
                $status = 'unavailable'; $booking = null;
            } elseif ($current) {
                $status = 'occupied'; $booking = $current;
            } elseif ($next) {
                $status = 'reserved'; $booking = $next;
            } else {
                $status = 'available'; $booking = null;
            }
            if ($statusFilter !== '' && $status !== $statusFilter) continue;
            if ($organizer !== '' && (!$booking || stripos((string) $booking['organizer_name'], $organizer) === false)) continue;
            $card = [
                'room' => $room,
                'status' => $status,
                'booking' => $booking ? [
                    'time' => time_12h($booking['start_time']) . ' – ' . time_12h($booking['end_time']),
                    'organizer' => $booking['organizer_name'],
                    'title' => $booking['schedule_title'],
                    'confirmed' => (int) ($booking['admin_confirmed'] ?? 0) === 1,
                ] : null,
            ];
            $key = (int) $room['map_id'];
            $grouped[$key]['map'] = ['map_id' => $key, 'map_name' => $room['map_name'], 'floor_number' => (int) $room['floor_number']];
            $grouped[$key]['cards'][] = $card;
            $total++;
        }
        view('rooms/index', [
            'title' => 'All Rooms',
            'grouped' => $grouped,
            'total' => $total,
            'maps' => $this->maps->all(true),
            'week' => $week,
            'week_start' => $weekStart,
            'prev_week' => $prevWeek,
            'next_week' => $nextWeek,
            'date' => $date,
            'time' => $time,
            'search' => $search,
            'map_id' => $mapId,
            'status' => $statusFilter,
            'organizer' => $organizer,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        $room = $this->rooms->find($id);
        if ($room === null) {
            flash('error', 'Room not found.');
            redirect('rooms');
        }
        $today = date('Y-m-d');
        view('rooms/show', [
            'title' => $room['room_name'],
            'room' => $room,
            'today' => $today,
            'today_occ' => $this->occurrences->forDateRange($today, $today, $id),
            'upcoming' => $this->occurrences->forDateRange($today, date('Y-m-d', strtotime('+30 days')), $id),
            'extra_js' => 'assets/js/timeline.js',
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();
        view('rooms/form', [
            'title' => 'New room', 'room' => null, 'maps' => $this->maps->all(true),
            'prefill_map' => isset($_GET['map_id']) ? (int) $_GET['map_id'] : null,
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('rooms');
        }
        $data = $this->formData();
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('rooms/create');
        }
        $id = $this->rooms->create($data);
        flash('success', 'Room created.');
        redirect('rooms/' . $id);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $room = $this->rooms->find($id);
        if ($room === null) {
            flash('error', 'Room not found.');
            redirect('rooms');
        }
        view('rooms/form', ['title' => 'Edit room', 'room' => $room, 'maps' => $this->maps->all(true), 'prefill_map' => null]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('rooms');
        }
        $data = $this->formData();
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('rooms/' . $id . '/edit');
        }
        $this->rooms->update($id, $data);
        flash('success', 'Room updated.');
        redirect('rooms/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('rooms');
        }
        try {
            $this->rooms->delete($id);
            flash('success', 'Room deleted.');
        } catch (\Throwable $e) {
            flash('error', 'Cannot delete this room - it still has schedules. Cancel those first.');
        }
        redirect('rooms');
    }

    private function formData(): array
    {
        $shapeType = in_array($_POST['shape_type'] ?? '', Room::SHAPE_TYPES, true)
            ? $_POST['shape_type'] : 'rectangle';
        
        
        $shapeJson = trim((string) ($_POST['shape_data'] ?? ''));
        if ($shapeJson === '') {
            $shape = $shapeType === 'polygon'
                ? ['type' => 'polygon', 'points' => [
                    ['x' => 20, 'y' => 20], ['x' => 180, 'y' => 20], ['x' => 180, 'y' => 100], ['x' => 20, 'y' => 100]]]
                : ['type' => 'rectangle', 'x' => (int) ($_POST['shape_x'] ?? 20), 'y' => (int) ($_POST['shape_y'] ?? 20),
                   'width' => (int) ($_POST['shape_w'] ?? 260), 'height' => (int) ($_POST['shape_h'] ?? 180)];
            $shapeJson = json_encode($shape);
        }
        return [
            'map_id'           => (int) ($_POST['map_id'] ?? 0),
            'room_name'        => trim((string) ($_POST['room_name'] ?? '')),
            'shape_type'       => $shapeType,
            'shape_data'       => $shapeJson,
            'room_description' => trim((string) ($_POST['room_description'] ?? '')) ?: null,
            'room_status'      => in_array($_POST['room_status'] ?? '', Room::STATUSES, true)
                ? $_POST['room_status'] : 'active',
        ];
    }

    private function validate(array $data): ?string
    {
        if ($data['map_id'] <= 0 || $this->maps->find($data['map_id']) === null) {
            return 'Choose a valid floor map.';
        }
        if ($data['room_name'] === '') {
            return 'Room name is required.';
        }
        $shape = json_decode($data['shape_data'], true);
        if (!is_array($shape)) {
            return 'Shape data must be valid JSON (see the examples on the form).';
        }
        return null;
    }
}
