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
        $todayOcc = $this->occurrences->forDateRange($today, $today, null);
        $busyIds = [];
        foreach ($todayOcc as $occ) {
            if ($occ['start_time'] <= $now && $occ['end_time'] > $now && $occ['occurrence_status'] === 'scheduled') {
                $busyIds[(int) $occ['room_id']] = $occ;
            }
        }
        view('maps/show', [
            'title' => $map['map_name'],
            'map' => $map, 'maps' => $this->maps->all(true), 'rooms' => $rooms,
            'busy' => $busyIds, 'today' => $todayOcc, 'today_date' => $today,
            'extra_js' => 'assets/js/map.js',
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

    /**
     * Interactive map editor (admin): drag rooms to move them, drag the
     * corner handle to resize rectangles, edit properties in the side
     * panel and watch the map redesign live. Saved via saveLayout().
     */
    public function editor(int $id): void
    {
        Auth::requireAdmin();
        $map = $this->maps->find($id);
        if ($map === null) {
            flash('error', 'Map not found.');
            redirect('maps');
        }
        $today = date('Y-m-d');
        $now = date('H:i:s');
        $busyIds = [];
        foreach ($this->occurrences->forDateRange($today, $today, null) as $occ) {
            if ($occ['start_time'] <= $now && $occ['end_time'] > $now && $occ['occurrence_status'] === 'scheduled') {
                $busyIds[(int) $occ['room_id']] = $occ;
            }
        }
        view('maps/editor', [
            'title' => 'Edit map layout — ' . $map['map_name'],
            'map' => $map, 'maps' => $this->maps->all(true),
            'rooms' => $this->rooms->all($id), 'busy' => $busyIds,
            'today' => $today,
            'extra_js' => 'assets/js/map-editor.js',
        ]);
    }

    /**
     * Save a layout from the editor: POST with _csrf + `layout` (JSON):
     * [{room_id|null, room_name, shape_type, shape_data, room_description,
     * room_status}] and optional `deleted` (JSON array of room ids).
     * Returns JSON (the editor posts with fetch).
     */
    public function saveLayout(int $id): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');
        if (!is_post() || !verify_csrf()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
            return;
        }
        $map = $this->maps->find($id);
        if ($map === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Map not found.']);
            return;
        }
        $layout = json_decode((string) ($_POST['layout'] ?? '[]'), true);
        $deleted = json_decode((string) ($_POST['deleted'] ?? '[]'), true);
        if (!is_array($layout)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Bad layout data.']);
            return;
        }
        $saved = 0; $created = 0;
        foreach ($layout as $r) {
            if (!is_array($r) || trim((string) ($r['room_name'] ?? '')) === '') continue;
            $shape = $r['shape_data'] ?? [];
            if (is_string($shape)) $shape = json_decode($shape, true) ?: [];
            $data = [
                'map_id' => $id,
                'room_name' => trim((string) $r['room_name']),
                'shape_type' => in_array($r['shape_type'] ?? '', Room::SHAPE_TYPES, true) ? $r['shape_type'] : 'rectangle',
                'shape_data' => json_encode($shape),
                'room_description' => trim((string) ($r['room_description'] ?? '')) ?: null,
                'room_status' => in_array($r['room_status'] ?? '', Room::STATUSES, true) ? $r['room_status'] : 'active',
            ];
            $rid = (int) ($r['room_id'] ?? 0);
            if ($rid > 0 && $this->rooms->find($rid) !== null) {
                $this->rooms->update($rid, $data);
                $saved++;
            } else {
                $this->rooms->create($data);
                $created++;
            }
        }
        if (is_array($deleted)) {
            foreach ($deleted as $did) {
                $this->rooms->delete((int) $did);
            }
        }
        echo json_encode(['ok' => true, 'saved' => $saved, 'created' => $created, 'deleted' => is_array($deleted) ? count($deleted) : 0]);
    }

    /** Download the map + its rooms as JSON (Figma Map View: Export). */
    public function exportMap(int $id): void
    {
        Auth::requireAdmin();
        $map = $this->maps->find($id);
        if ($map === null) {
            flash('error', 'Map not found.');
            redirect('maps');
        }
        $rooms = $this->rooms->all($id);
        foreach ($rooms as &$r) {
            $r['shape_data'] = json_decode((string) ($r['shape_data'] ?? ''), true);
        }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="map-' . $id . '.json"');
        echo json_encode(['map' => $map, 'rooms' => $rooms], JSON_PRETTY_PRINT);
        exit;
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
