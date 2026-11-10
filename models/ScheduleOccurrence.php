<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;


final class ScheduleOccurrence
{
    public const STATUSES = ['scheduled', 'cancelled', 'completed'];

    public function __construct(private Database $db) {}

    public function find(int $id): ?array
    {
        return $this->db->one(
            'SELECT o.*, s.schedule_title, s.organizer_id, r.room_name, m.map_name
             FROM tblScheduleOccurrences o
             JOIN tblSchedules s ON s.schedule_id = o.schedule_id
             JOIN tblRooms r ON r.room_id = o.room_id
             JOIN tblMaps m ON m.map_id = r.map_id
             WHERE o.occurrence_id = ?',
            [$id]
        );
    }

    
    public function forDateRange(string $from, string $to, ?int $roomId = null): array
    {
        $sql = 'SELECT o.*, s.schedule_title, s.schedule_description, s.organizer_id, s.admin_confirmed,
                       r.room_name, m.map_name, u.username AS organizer_name
                FROM tblScheduleOccurrences o
                JOIN tblSchedules s ON s.schedule_id = o.schedule_id
                JOIN tblRooms r ON r.room_id = o.room_id
                JOIN tblMaps m ON m.map_id = r.map_id
                JOIN tblUsers u ON u.user_id = s.organizer_id
                WHERE o.occurrence_date BETWEEN ? AND ?';
        $params = [$from, $to];
        if ($roomId !== null) {
            $sql .= ' AND o.room_id = ?';
            $params[] = $roomId;
        }
        return $this->db->all($sql . ' ORDER BY o.occurrence_date, o.start_time, r.room_name', $params);
    }

    
    public function forSchedule(int $scheduleId): array
    {
        return $this->db->all(
            'SELECT * FROM tblScheduleOccurrences WHERE schedule_id = ? ORDER BY occurrence_date, start_time',
            [$scheduleId]
        );
    }

    
    public function upcoming(int $limit = 10, ?int $organizerId = null): array
    {
        $sql = 'SELECT o.*, s.schedule_title, r.room_name, m.map_name
                FROM tblScheduleOccurrences o
                JOIN tblSchedules s ON s.schedule_id = o.schedule_id
                JOIN tblRooms r ON r.room_id = o.room_id
                JOIN tblMaps m ON m.map_id = r.map_id
                WHERE o.occurrence_status = ? AND o.occurrence_date >= ?';
        $params = ['scheduled', date('Y-m-d')];
        if ($organizerId !== null) {
            $sql .= ' AND s.organizer_id = ?';
            $params[] = $organizerId;
        }
        
        return $this->db->all($sql . ' ORDER BY o.occurrence_date, o.start_time LIMIT ' . (int) $limit, $params);
    }

    public function insert(int $scheduleId, string $date, int $roomId, string $start, string $end, string $status = 'scheduled'): void
    {
        $driver = $this->db->driver();
        $sql = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO tblScheduleOccurrences (schedule_id, occurrence_date, room_id, start_time, end_time, occurrence_status) VALUES (?, ?, ?, ?, ?, ?)'
            : 'INSERT IGNORE INTO tblScheduleOccurrences (schedule_id, occurrence_date, room_id, start_time, end_time, occurrence_status) VALUES (?, ?, ?, ?, ?, ?)';
        $this->db->run($sql, [$scheduleId, $date, $roomId, $start, $end, $status]);
    }

    public function deleteForSchedule(int $scheduleId): void
    {
        $this->db->run('DELETE FROM tblScheduleOccurrences WHERE schedule_id = ?', [$scheduleId]);
    }

    public function deleteFutureForSchedule(int $scheduleId, string $fromDate): void
    {
        $this->db->run(
            'DELETE FROM tblScheduleOccurrences WHERE schedule_id = ? AND occurrence_date >= ?',
            [$scheduleId, $fromDate]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db->run(
            'UPDATE tblScheduleOccurrences SET occurrence_status = ? WHERE occurrence_id = ?',
            [$status, $id]
        );
    }

    
    public function hasOverlap(int $roomId, string $date, string $start, string $end, ?int $excludeScheduleId = null): bool
    {
        $sql = "SELECT 1 FROM tblScheduleOccurrences
                WHERE room_id = ? AND occurrence_date = ? AND occurrence_status <> 'cancelled'
                  AND start_time < ? AND end_time > ? LIMIT 1";
        $params = [$roomId, $date, $end, $start];
        if ($excludeScheduleId !== null) {
            $sql = "SELECT 1 FROM tblScheduleOccurrences
                    WHERE room_id = ? AND occurrence_date = ? AND occurrence_status <> 'cancelled'
                      AND schedule_id <> ? AND start_time < ? AND end_time > ? LIMIT 1";
            $params = [$roomId, $date, $excludeScheduleId, $end, $start];
        }
        return $this->db->one($sql, $params) !== null;
    }
}
