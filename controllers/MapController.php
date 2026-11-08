<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Map;
use App\Models\Room;
use App\Models\ScheduleOccurrence;

final class MapController
{
    public function __construct(
        private Map $maps,
        private Room $rooms,
        private ScheduleOccurrence $occurrences
    ) {}

    public function index(): void
    {
        Auth::requireLogin();
        view('maps/index', ['title' => 'Floor maps', 'maps' => $this->maps->all()]);
    }

    /** Interactive floor map: rooms drawn as SVG, coloured by live status. */
    public function show(int $id): void
    {
        Auth::requireLogin();
        $map = $this->maps->find($id);
        if ($map === null) {
            flash('error', 'Map not found.');
            redirect('maps');
        }
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $rooms = $this->rooms->all($id);
        $busyIds = [];
        foreach ($this->occurrences->forDateRange($today, $today, null) as $occ) {
            if ($occ['start_time'] <= $now && $occ['end_time'] > $now && $occ['occurrence_status'] === 'scheduled') {
                $busyIds[(int) $occ['room_id']] = $occ;
            }
        }
        view('maps/show', [
            'title' => $map['map_name'],
            'map' => $map, 'rooms' => $rooms, 'busy' => $busyIds, 'today' => $today,
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();
        view('maps/form', ['title' => 'New floor map', 'map' => null]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('maps');
        }
        $data = $this->formData();
        if ($data['map_name'] === '' || $data['floor_number'] < 0) {
            flash('error', 'Map name and a valid floor number are required.');
            redirect('maps/create');
        }
        $id = $this->maps->create($data);
        flash('success', 'Map created.');
        redirect('maps/' . $id);
    }

    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $map = $this->maps->find($id);
        if ($map === null) {
            flash('error', 'Map not found.');
            redirect('maps');
        }
        view('maps/form', ['title' => 'Edit map', 'map' => $map]);
    }

    public function update(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('maps');
        }
        $this->maps->update($id, $this->formData());
        flash('success', 'Map updated.');
        redirect('maps/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('maps');
        }
        $this->maps->delete($id);
        flash('success', 'Map and its rooms deleted.');
        redirect('maps');
    }

    private function formData(): array
    {
        return [
            'map_name'         => trim((string) ($_POST['map_name'] ?? '')),
            'floor_number'     => (int) ($_POST['floor_number'] ?? 1),
            'map_width'        => (float) ($_POST['map_width'] ?? 1200),
            'map_height'       => (float) ($_POST['map_height'] ?? 800),
            'background_image' => trim((string) ($_POST['background_image'] ?? '')) ?: null,
            'map_status'       => in_array($_POST['map_status'] ?? '', ['active', 'archived'], true)
                ? $_POST['map_status'] : 'active',
        ];
    }
}
