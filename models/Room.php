<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;


final class Room
{
    public const SHAPE_TYPES = ['polygon', 'rectangle', 'path'];
    public const STATUSES = ['active', 'unavailable'];

    public function __construct(private Database $db) {}

    public function find(int $id): ?array
    {
        return $this->db->one(
            'SELECT r.*, m.map_name, m.floor_number, m.map_width, m.map_height
             FROM tblRooms r JOIN tblMaps m ON m.map_id = r.map_id
             WHERE r.room_id = ?',
            [$id]
        );
    }

    
    public function all(?int $mapId = null, bool $activeOnly = false): array
    {
        $sql = 'SELECT r.*, m.map_name, m.floor_number
                FROM tblRooms r JOIN tblMaps m ON m.map_id = r.map_id';
        $params = [];
        $where = [];
        if ($mapId !== null) {
            $where[] = 'r.map_id = ?';
            $params[] = $mapId;
        }
        if ($activeOnly) {
            $where[] = "r.room_status = 'active'";
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        return $this->db->all($sql . ' ORDER BY m.floor_number, r.room_name', $params);
    }

    public function create(array $data): int
    {
        $this->db->run(
            'INSERT INTO tblRooms (map_id, room_name, shape_type, shape_data, room_description, room_status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$data['map_id'], $data['room_name'], $data['shape_type'],
             $data['shape_data'], $data['room_description'] ?? null,
             $data['room_status'] ?? 'active']
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->run(
            'UPDATE tblRooms SET map_id = ?, room_name = ?, shape_type = ?, shape_data = ?,
             room_description = ?, room_status = ? WHERE room_id = ?',
            [$data['map_id'], $data['room_name'], $data['shape_type'],
             $data['shape_data'], $data['room_description'] ?? null,
             $data['room_status'] ?? 'active', $id]
        );
    }

    public function delete(int $id): void
    {
        $this->db->run('DELETE FROM tblRooms WHERE room_id = ?', [$id]);
    }

    
    public static function shapeToSvg(array $room, string $fill, string $extra = ''): string
    {
        $shape = json_decode((string) ($room['shape_data'] ?? ''), true) ?: [];
        $type = $room['shape_type'] ?? ($shape['type'] ?? 'rectangle');
        $attrs = sprintf('data-room-id="%d" fill="%s" %s', (int) $room['room_id'], e($fill), $extra);

        if ($type === 'rectangle') {
            return sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" rx="6" %s />',
                e((string) ($shape['x'] ?? 0)), e((string) ($shape['y'] ?? 0)),
                e((string) ($shape['width'] ?? 100)), e((string) ($shape['height'] ?? 80)), $attrs
            );
        }
        if ($type === 'path' && !empty($shape['d'])) {
            return sprintf('<path d="%s" %s />', e((string) $shape['d']), $attrs);
        }
        $points = '';
        foreach ($shape['points'] ?? [] as $p) {
            $points .= ((float) ($p['x'] ?? 0)) . ',' . ((float) ($p['y'] ?? 0)) . ' ';
        }
        return sprintf('<polygon points="%s" %s />', e(trim($points)), $attrs);
    }

    
    public static function shapeCentre(array $room): array
    {
        $shape = json_decode((string) ($room['shape_data'] ?? ''), true) ?: [];
        $type = $room['shape_type'] ?? ($shape['type'] ?? 'rectangle');
        if ($type === 'rectangle') {
            return [
                (float) ($shape['x'] ?? 0) + (float) ($shape['width'] ?? 100) / 2,
                (float) ($shape['y'] ?? 0) + (float) ($shape['height'] ?? 80) / 2,
            ];
        }
        $xs = $ys = [];
        foreach ($shape['points'] ?? [] as $p) {
            $xs[] = (float) ($p['x'] ?? 0);
            $ys[] = (float) ($p['y'] ?? 0);
        }
        if (!$xs) {
            return [50, 50];
        }
        return [(min($xs) + max($xs)) / 2, (min($ys) + max($ys)) / 2];
    }
}
