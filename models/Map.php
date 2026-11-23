<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;


final class Map
{
    public function __construct(private Database $db) {}

    public function find(int $id): ?array
    {
        return $this->db->one('SELECT * FROM tblMaps WHERE map_id = ?', [$id]);
    }

    
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
        
        $this->db->run('DELETE FROM tblMaps WHERE map_id = ?', [$id]);
    }

    public function updateDecorations(int $id, string $decorationsJson): void
    {
        $this->db->run('UPDATE tblMaps SET decorations = ? WHERE map_id = ?', [$decorationsJson, $id]);
    }

    public static function decorationsToSvg(array $decorations): string
    {
        $out = '';
        foreach ($decorations as $d) {
            if (!is_array($d)) continue;
            if (($d['type'] ?? '') === 'text') {
                $text = (string) ($d['text'] ?? '');
                if (trim($text) === '') continue;
                $out .= sprintf(
                    '<text x="%s" y="%s" font-size="%s" fill="%s" font-weight="600" text-anchor="middle">%s</text>',
                    (float) ($d['x'] ?? 0), (float) ($d['y'] ?? 0),
                    max(10, (int) ($d['size'] ?? 20)),
                    htmlspecialchars((string) ($d['color'] ?? '#64748b'), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($text, ENT_QUOTES, 'UTF-8')
                );
                continue;
            }
            $x = (float) ($d['x'] ?? 0);
            $y = (float) ($d['y'] ?? 0);
            $w = max(10, (float) ($d['width'] ?? 160));
            $h = max(10, (float) ($d['height'] ?? 100));
            $fill = (string) ($d['fill'] ?? '#e2e8f0');
            $out .= sprintf(
                '<rect x="%s" y="%s" width="%s" height="%s" rx="6" fill="%s" stroke="%s" stroke-width="1" />',
                $x, $y, $w, $h,
                htmlspecialchars($fill, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($fill, ENT_QUOTES, 'UTF-8')
            );
            $label = trim((string) ($d['label'] ?? ''));
            if ($label !== '') {
                $out .= sprintf(
                    '<text x="%s" y="%s" font-size="14" fill="#475569" font-weight="600" text-anchor="middle">%s</text>',
                    $x + $w / 2, $y + $h / 2 + 5,
                    htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                );
            }
        }
        return $out;
    }
}
