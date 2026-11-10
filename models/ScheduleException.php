<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;


final class ScheduleException
{
    public const TYPES = ['skipped', 'moved', 'modified'];

    public function __construct(private Database $db) {}

    
    public function forSchedule(int $scheduleId): array
    {
        return $this->db->all(
            'SELECT e.*, u.username AS created_by_name, r.room_name AS new_room_name
             FROM tblScheduleExceptions e
             JOIN tblUsers u ON u.user_id = e.created_by
             LEFT JOIN tblRooms r ON r.room_id = e.new_room_id
             WHERE e.schedule_id = ? ORDER BY e.original_date',
            [$scheduleId]
        );
    }

    public function findForDate(int $scheduleId, string $date): ?array
    {
        return $this->db->one(
            'SELECT * FROM tblScheduleExceptions WHERE schedule_id = ? AND original_date = ?',
            [$scheduleId, $date]
        );
    }

    public function create(array $data): int
    {
        $this->db->run(
            'INSERT INTO tblScheduleExceptions
             (schedule_id, original_date, exception_type, new_date, new_room_id,
              new_start_time, new_end_time, exception_reason, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$data['schedule_id'], $data['original_date'], $data['exception_type'],
             $data['new_date'] ?? null, $data['new_room_id'] ?? null,
             $data['new_start_time'] ?? null, $data['new_end_time'] ?? null,
             $data['exception_reason'] ?? null, $data['created_by']]
        );
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->db->run('DELETE FROM tblScheduleExceptions WHERE exception_id = ?', [$id]);
    }
}
