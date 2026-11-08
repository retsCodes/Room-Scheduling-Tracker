<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** tblMaps */
final class Map
{
    public function __construct(private Database $db) {}

    public function find(int $id): ?array
    {
        return $this->db->one('SELECT * FROM tblMaps WHERE map_id = ?', [$id]);
    }

    /** @return array<int, array> */
    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT m.*, (SELECT COUNT(*) FROM tblRooms r WHERE r.map_id = m.map_id) AS room_count
                FROM tblMaps m';
        if ($activeOnly) {
            $sql .= " WHERE m.map_status = 'active'";
        }
        return $this->db->all($sql . ' ORDER BY m.floor_number, m.map_name');
    }

    public function create(array $data): int
    {
        $this->db->run(
            'INSERT INTO tblMaps (map_name, floor_number, map_width, map_height, background_image, map_status)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$data['map_name'], (int) $data['floor_number'], (float) $data['map_width'],
             (float) $data['map_height'], $data['background_image'] ?? null,
             $data['map_status'] ?? 'active']
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->db->run(
            'UPDATE tblMaps SET map_name = ?, floor_number = ?, map_width = ?, map_height = ?,
             background_image = ?, map_status = ? WHERE map_id = ?',
            [$data['map_name'], (int) $data['floor_number'], (float) $data['map_width'],
             (float) $data['map_height'], $data['background_image'] ?? null,
             $data['map_status'] ?? 'active', $id]
        );
    }

    public function delete(int $id): void
    {
        // Rooms on this map are deleted by the FK (ON DELETE CASCADE).
        $this->db->run('DELETE FROM tblMaps WHERE map_id = ?', [$id]);
    }
}
