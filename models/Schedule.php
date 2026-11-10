<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;


final class Schedule
{
    public const RECURRENCE_TYPES = ['one_time', 'daily', 'weekly', 'monthly'];
    public const STATUSES = ['active', 'cancelled', 'archived'];

    public function __construct(private Database $db) {}

    public function find(int $id): ?array
    {
        $row = $this->db->one(
            'SELECT s.*, r.room_name, m.map_name, u.username AS organizer_name
             FROM tblSchedules s
             JOIN tblRooms r ON r.room_id = s.room_id
             JOIN tblMaps m ON m.map_id = r.map_id
             JOIN tblUsers u ON u.user_id = s.organizer_id
             WHERE s.schedule_id = ?',
            [$id]
        );
        if ($row) {
            $row['days'] = $this->days($id);
        }
        return $row;
    }

    
    public function all(array $filters = []): array
    {
        $sql = 'SELECT s.*, r.room_name, m.map_name, u.username AS organizer_name
                FROM tblSchedules s
                JOIN tblRooms r ON r.room_id = s.room_id
                JOIN tblMaps m ON m.map_id = r.map_id
                JOIN tblUsers u ON u.user_id = s.organizer_id';
        $params = [];
        $where = [];
        if (!empty($filters['room_id'])) {
            $where[] = 's.room_id = ?';
            $params[] = (int) $filters['room_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 's.schedule_status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['organizer_id'])) {
            $where[] = 's.organizer_id = ?';
            $params[] = (int) $filters['organizer_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = '(s.schedule_title LIKE ? OR s.schedule_description LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $rows = $this->db->all($sql . ' ORDER BY s.start_date DESC, s.start_time', $params);
        foreach ($rows as &$row) {
            $row['days'] = $this->days((int) $row['schedule_id']);
        }
        return $rows;
    }

    
    public function days(int $scheduleId): array
    {
        $rows = $this->db->all(
            'SELECT day_of_week FROM tblScheduleDays WHERE schedule_id = ? ORDER BY day_of_week',
            [$scheduleId]
        );
        return array_map(fn ($r) => (int) $r['day_of_week'], $rows);
    }

    public function create(array $data): int
    {
        $this->db->run(
            'INSERT INTO tblSchedules
             (organizer_id, room_id, schedule_title, schedule_description, recurrence_type,
              start_date, end_date, start_time, end_time, schedule_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$data['organizer_id'], $data['room_id'], $data['schedule_title'],
             $data['schedule_description'] ?? null, $data['recurrence_type'],
             $data['start_date'], $data['end_date'] ?? null,
             $data['start_time'], $data['end_time'], $data['schedule_status'] ?? 'active']
        );
        $id = (int) $this->db->lastInsertId();
        $this->saveDays($id, $data['days'] ?? []);
        return $id;
    }

    public function update(int $id, array $data): void
    {
        $this->db->run(
            'UPDATE tblSchedules SET room_id = ?, schedule_title = ?, schedule_description = ?,
             recurrence_type = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?,
             schedule_status = ? WHERE schedule_id = ?',
            [$data['room_id'], $data['schedule_title'], $data['schedule_description'] ?? null,
             $data['recurrence_type'], $data['start_date'], $data['end_date'] ?? null,
             $data['start_time'], $data['end_time'], $data['schedule_status'] ?? 'active', $id]
        );
        $this->saveDays($id, $data['days'] ?? []);
    }

    private function saveDays(int $scheduleId, array $days): void
    {
        $this->db->run('DELETE FROM tblScheduleDays WHERE schedule_id = ?', [$scheduleId]);
        foreach (array_unique(array_map('intval', $days)) as $day) {
            if ($day >= 1 && $day <= 7) {
                $this->db->run(
                    'INSERT INTO tblScheduleDays (schedule_id, day_of_week) VALUES (?, ?)',
                    [$scheduleId, $day]
                );
            }
        }
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->run(
            'UPDATE tblSchedules SET schedule_status = ? WHERE schedule_id = ?',
            [$status, $id]
        );
    }

    public function setConfirmed(int $id, bool $confirmed): void
    {
        $this->db->run(
            'UPDATE tblSchedules SET admin_confirmed = ? WHERE schedule_id = ?',
            [$confirmed ? 1 : 0, $id]
        );
    }

    public function delete(int $id): void
    {
        
        $this->db->run('DELETE FROM tblSchedules WHERE schedule_id = ?', [$id]);
    }
}
