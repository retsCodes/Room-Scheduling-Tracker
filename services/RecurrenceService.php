<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\ScheduleException;
use App\Models\ScheduleOccurrence;


final class RecurrenceService
{
    public const GENERATION_DAYS = 90;

    public function __construct(
        private ScheduleOccurrence $occurrences,
        private ScheduleException $exceptions
    ) {}

    
    public function generate(array $schedule, bool $clearExisting = true): array
    {
        $id = (int) $schedule['schedule_id'];
        $start = new \DateTimeImmutable((string) $schedule['start_date']);
        $end = !empty($schedule['end_date'])
            ? new \DateTimeImmutable((string) $schedule['end_date'])
            : $start->modify('+' . (self::GENERATION_DAYS - 1) . ' days');
        
        $cap = $start->modify('+364 days');
        if ($end > $cap) {
            $end = $cap;
        }

        if ($clearExisting) {
            $this->occurrences->deleteForSchedule($id);
        }

        $dates = $this->datesFor($schedule, $start, $end);
        $generated = [];
        foreach ($dates as $date) {
            $exception = $this->exceptions->findForDate($id, $date);
            if ($exception !== null) {
                $this->applyException($schedule, $exception);
                continue;
            }
            $this->occurrences->insert(
                $id, $date, (int) $schedule['room_id'],
                (string) $schedule['start_time'], (string) $schedule['end_time']
            );
            $generated[] = $date;
        }
        return $generated;
    }

    
    public function datesFor(array $schedule, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $type = $schedule['recurrence_type'] ?? 'one_time';
        $dates = [];
        $cursor = $start;
        $days = array_map('intval', $schedule['days'] ?? []);

        while ($cursor <= $end) {
            $include = match ($type) {
                'one_time' => $cursor == $start,
                'daily'    => true,
                'weekly'   => in_array((int) $cursor->format('N'), $days, true),
                'monthly'  => (int) $cursor->format('j') === (int) $start->format('j'),
                default    => false,
            };
            if ($include) {
                $dates[] = $cursor->format('Y-m-d');
            }
            if ($type === 'one_time') {
                break;
            }
            $cursor = $type === 'monthly'
                ? $cursor->modify('+1 month')
                : $cursor->modify('+1 day');
            if (count($dates) > 400) {
                break; 
            }
        }
        return $dates;
    }

    private function applyException(array $schedule, array $exception): void
    {
        $id = (int) $schedule['schedule_id'];
        $original = (string) $exception['original_date'];
        $type = $exception['exception_type'];

        if ($type === 'skipped') {
            $this->occurrences->insert(
                $id, $original, (int) $schedule['room_id'],
                (string) $schedule['start_time'], (string) $schedule['end_time'], 'cancelled'
            );
            return;
        }
        
        $this->occurrences->insert(
            $id, $original, (int) $schedule['room_id'],
            (string) $schedule['start_time'], (string) $schedule['end_time'], 'cancelled'
        );
        $newDate = $exception['new_date'] ?: $original;
        $this->occurrences->insert(
            $id, (string) $newDate,
            (int) ($exception['new_room_id'] ?? $schedule['room_id']),
            (string) ($exception['new_start_time'] ?? $schedule['start_time']),
            (string) ($exception['new_end_time'] ?? $schedule['end_time'])
        );
    }
}
