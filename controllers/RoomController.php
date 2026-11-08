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
        $mapId = isset($_GET['map_id']) && $_GET['map_id'] !== '' ? (int) $_GET['map_id'] : null;
        view('rooms/index', [
            'title' => 'Rooms',
            'rooms' => $this->rooms->all($mapId),
            'maps'  => $this->maps->all(),
            'map_id' => $mapId,
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
            'upcoming' => $this->occurrences->forDateRange($today, date('Y-m-d', strtotime('+30 days')), $id),
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
            flash('error', 'Cannot delete this room — it still has schedules. Cancel those first.');
        }
        redirect('rooms');
    }

    private function formData(): array
    {
        $shapeType = in_array($_POST['shape_type'] ?? '', Room::SHAPE_TYPES, true)
            ? $_POST['shape_type'] : 'rectangle';
        // Shape is entered as JSON in the form; fall back to a simple
        // rectangle built from x/y/w/h fields when JSON is not given.
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
