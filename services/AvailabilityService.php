<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Room;
use App\Models\ScheduleOccurrence;

/**
 * Room availability: a room is free when it is active and has no
 * non-cancelled occurrence overlapping the requested date/time.
 * Overlap rule: existing.start < requested.end AND existing.end > requested.start.
 * Back-to-back bookings (one ends exactly when the next starts) do NOT conflict.
 */
final class AvailabilityService
{
    public function __construct(
        private ScheduleOccurrence $occurrences,
        private Room $rooms
    ) {}

    public function isFree(int $roomId, string $date, string $start, string $end, ?int $excludeScheduleId = null): bool
    {
        $room = $this->rooms->find($roomId);
        if ($room === null || $room['room_status'] !== 'active') {
            return false;
        }
        return !$this->occurrences->hasOverlap($roomId, $date, $start, $end, $excludeScheduleId);
    }

    /**
     * Check a whole recurring series for conflicts before saving.
     * @return string[] Human-readable conflict descriptions (empty = no conflict).
     */
    public function checkSeries(array $schedule, array $dates, ?int $excludeScheduleId = null): array
    {
        $conflicts = [];
        foreach ($dates as $date) {
            if ($this->occurrences->hasOverlap(
                (int) $schedule['room_id'], $date,
                (string) $schedule['start_time'], (string) $schedule['end_time'],
                $excludeScheduleId
            )) {
                $conflicts[] = sprintf(
                    '%s %s–%s is already booked',
                    $date, substr((string) $schedule['start_time'], 0, 5), substr((string) $schedule['end_time'], 0, 5)
                );
            }
        }
        return $conflicts;
    }

    /**
     * @return array<int, array{room: array, free: bool, busy_until: ?string}>
     * Status of every active room at a given date/time ("now" by default).
     */
    public function roomStatusAt(string $date, string $time): array
    {
        $out = [];
        foreach ($this->rooms->all(null, true) as $room) {
            // A 1-minute window starting at $time tells us if the room is busy then.
            $windowEnd = date('H:i:s', min(strtotime($time) + 60, strtotime('23:59:59')));
            $busy = $this->occurrences->hasOverlap((int) $room['room_id'], $date, $time, $windowEnd);
            $out[] = ['room' => $room, 'free' => !$busy, 'busy_until' => null];
        }
        return $out;
    }

    /** Free rooms for a date/time slot, optionally on one map only. */
    public function freeRooms(string $date, string $start, string $end, ?int $mapId = null): array
    {
        $free = [];
        foreach ($this->rooms->all($mapId, true) as $room) {
            if ($this->isFree((int) $room['room_id'], $date, $start, $end)) {
                $free[] = $room;
            }
        }
        return $free;
    }
}
