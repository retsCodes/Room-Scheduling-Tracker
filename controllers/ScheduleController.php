<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleException;
use App\Models\ScheduleOccurrence;
use App\Services\AvailabilityService;
use App\Services\RecurrenceService;

final class ScheduleController
{
    public function __construct(
        private Database $db,
        private Schedule $schedules,
        private ScheduleOccurrence $occurrences,
        private ScheduleException $exceptions,
        private Room $rooms,
        private RecurrenceService $recurrence,
        private AvailabilityService $availability
    ) {}

    public function index(): void
    {
        Auth::requireLogin();
        $filters = [
            'room_id' => isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int) $_GET['room_id'] : null,
            'status'  => $_GET['status'] ?? 'active',
            'search'  => trim((string) ($_GET['search'] ?? '')),
        ];
        $filters = array_filter($filters, fn ($v) => $v !== '' && $v !== null);
        view('schedules/index', [
            'title' => 'Schedules',
            'schedules' => $this->schedules->all($filters),
            'rooms' => $this->rooms->all(),
            'filters' => $filters,
        ]);
    }

    
    public function calendar(): void
    {
        Auth::requireLogin();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $first = $month . '-01';
        $last = date('Y-m-t', strtotime($first));
        $roomId = isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int) $_GET['room_id'] : null;
        $occurrences = $this->occurrences->forDateRange($first, $last, $roomId);
        $byDate = [];
        foreach ($occurrences as $occ) {
            $byDate[$occ['occurrence_date']][] = $occ;
        }
        view('schedules/calendar', [
            'title' => 'Calendar - ' . date('F Y', strtotime($first)),
            'month' => $month, 'first' => $first, 'by_date' => $byDate,
            'rooms' => $this->rooms->all(), 'room_id' => $roomId,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireLogin();
        $schedule = $this->schedules->find($id);
        if ($schedule === null) {
            flash('error', 'Schedule not found.');
            redirect('schedules');
        }
        view('schedules/show', [
            'title' => $schedule['schedule_title'],
            'schedule' => $schedule,
            'occurrences' => $this->occurrences->forSchedule($id),
            'exceptions' => $this->exceptions->forSchedule($id),
            'rooms' => $this->rooms->all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireCanBook();
        $prefill = [
            'room_id' => isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0,
            'start_date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['date'] ?? '')) ? $_GET['date'] : date('Y-m-d'),
            'start_time' => preg_match('/^\d{2}:\d{2}$/', (string) ($_GET['start_time'] ?? '')) ? $_GET['start_time'] : '09:00',
            'end_time' => preg_match('/^\d{2}:\d{2}$/', (string) ($_GET['end_time'] ?? '')) ? $_GET['end_time'] : '10:00',
        ];
        view('schedules/form', [
            'title' => 'New schedule', 'schedule' => null, 'rooms' => $this->rooms->all(null, true),
            'prefill' => $prefill,
        ]);
    }

    public function store(): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules');
        }
        $data = $this->formData();
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('schedules/create');
        }
        $dates = $this->recurrence->datesFor(
            $data + ['schedule_id' => 0],
            new \DateTimeImmutable($data['start_date']),
            new \DateTimeImmutable($data['end_date'] ?? $data['start_date'])
        );
        $conflicts = $this->availability->checkSeries($data, $dates);
        $this->db->begin();
        try {
            $id = $this->schedules->create($data);
            $saved = $this->schedules->find($id);
            $this->recurrence->generate($saved);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            flash('error', 'Could not save the schedule: ' . $e->getMessage());
            redirect('schedules/create');
        }
        $msg = 'Schedule created with ' . count($dates) . ' occurrence(s).';
        if ($conflicts) {
            $msg .= ' It overlaps ' . count($conflicts) . ' existing booking(s) - an admin can confirm which one goes ahead.';
        }
        flash('success', $msg);
        redirect('schedules/' . $id);
    }

    public function edit(int $id): void
    {
        Auth::requireCanBook();
        $schedule = $this->schedules->find($id);
        if ($schedule === null) {
            flash('error', 'Schedule not found.');
            redirect('schedules');
        }
        if (!Auth::isAdmin() && (int) $schedule['organizer_id'] !== Auth::id()) {
            flash('error', 'You can only edit your own schedules.');
            redirect('schedules/' . $id);
        }
        view('schedules/form', ['title' => 'Edit schedule', 'schedule' => $schedule, 'rooms' => $this->rooms->all(null, true)]);
    }

    public function update(int $id): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules');
        }
        $existing = $this->schedules->find($id);
        if ($existing === null) {
            flash('error', 'Schedule not found.');
            redirect('schedules');
        }
        $data = $this->formData();
        $data['organizer_id'] = (int) $existing['organizer_id'];
        $error = $this->validate($data);
        if ($error) {
            flash('error', $error);
            redirect('schedules/' . $id . '/edit');
        }
        $dates = $this->recurrence->datesFor(
            $data + ['schedule_id' => $id],
            new \DateTimeImmutable($data['start_date']),
            new \DateTimeImmutable($data['end_date'] ?? $data['start_date'])
        );
        $conflicts = $this->availability->checkSeries($data, $dates, $id);
        $this->db->begin();
        try {
            $this->schedules->update($id, $data);
            $saved = $this->schedules->find($id);
            $this->recurrence->generate($saved);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            flash('error', 'Could not update the schedule: ' . $e->getMessage());
            redirect('schedules/' . $id . '/edit');
        }
        $msg = 'Schedule updated and occurrences regenerated.';
        if ($conflicts) {
            $msg .= ' It now overlaps ' . count($conflicts) . ' existing booking(s) - an admin can confirm which one goes ahead.';
        }
        flash('success', $msg);
        redirect('schedules/' . $id);
    }

    
    public function confirm(int $id): void
    {
        Auth::requireAdmin();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules/' . $id);
        }
        if ($this->schedules->find($id) === null) {
            flash('error', 'Schedule not found.');
            redirect('schedules');
        }
        $confirmed = ($_POST['confirmed'] ?? '') === '1';
        $this->schedules->setConfirmed($id, $confirmed);
        flash('success', $confirmed ? 'Schedule confirmed by admin.' : 'Admin confirmation removed.');
        redirect('schedules/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules');
        }
        $this->schedules->delete($id);
        flash('success', 'Schedule deleted.');
        redirect('schedules');
    }

    
    public function cancel(int $id): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules');
        }
        $this->schedules->setStatus($id, 'cancelled');
        $this->occurrences->deleteFutureForSchedule($id, date('Y-m-d'));
        flash('success', 'Schedule cancelled.');
        redirect('schedules/' . $id);
    }

    
    public function storeException(int $id): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules/' . $id);
        }
        $schedule = $this->schedules->find($id);
        if ($schedule === null) {
            flash('error', 'Schedule not found.');
            redirect('schedules');
        }
        $asArray = fn($v) => is_array($v) ? $v : [$v];
        $originals = $asArray($_POST['original_date'] ?? []);
        $types = $asArray($_POST['exception_type'] ?? []);
        $newDates = $asArray($_POST['new_date'] ?? []);
        $newRooms = $asArray($_POST['new_room_id'] ?? []);
        $newStarts = $asArray($_POST['new_start_time'] ?? []);
        $newEnds = $asArray($_POST['new_end_time'] ?? []);
        $reasons = $asArray($_POST['exception_reason'] ?? []);
        $rows = [];
        foreach ($originals as $i => $original) {
            $original = (string) $original;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $original)) continue;
            $type = in_array($types[$i] ?? '', ScheduleException::TYPES, true) ? $types[$i] : 'skipped';
            $newDate = trim((string) ($newDates[$i] ?? '')) ?: null;
            if ($type === 'moved' && !$newDate) {
                flash('error', 'A moved exception needs a new date.');
                redirect('schedules/' . $id);
            }
            $rows[] = [
                'schedule_id' => $id,
                'original_date' => $original,
                'exception_type' => $type,
                'new_date' => $newDate,
                'new_room_id' => !empty($newRooms[$i]) ? (int) $newRooms[$i] : null,
                'new_start_time' => trim((string) ($newStarts[$i] ?? '')) ?: null,
                'new_end_time' => trim((string) ($newEnds[$i] ?? '')) ?: null,
                'exception_reason' => trim((string) ($reasons[$i] ?? '')) ?: null,
                'created_by' => Auth::id(),
            ];
        }
        if (empty($rows)) {
            flash('error', 'Add at least one exception with a valid original date.');
            redirect('schedules/' . $id);
        }
        $this->db->begin();
        try {
            foreach ($rows as $row) $this->exceptions->create($row);
            $saved = $this->schedules->find($id);
            $this->recurrence->generate($saved);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            flash('error', 'Could not save the exceptions: ' . $e->getMessage());
            redirect('schedules/' . $id);
        }
        flash('success', count($rows) === 1 ? 'Exception saved and occurrences updated.' : count($rows) . ' exceptions saved and occurrences updated.');
        redirect('schedules/' . $id);
    }

    public function deleteException(int $id, int $exceptionId): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('schedules/' . $id);
        }
        $schedule = $this->schedules->find($id);
        $ex = $this->exceptions->find($exceptionId);
        if ($schedule === null || $ex === null || (int) $ex['schedule_id'] !== $id) {
            flash('error', 'Exception not found.');
            redirect('schedules/' . $id);
        }
        $this->db->begin();
        try {
            $this->exceptions->delete($exceptionId);
            $saved = $this->schedules->find($id);
            $this->recurrence->generate($saved);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            flash('error', 'Could not remove the exception: ' . $e->getMessage());
            redirect('schedules/' . $id);
        }
        flash('success', 'Exception removed and occurrences updated.');
        redirect('schedules/' . $id);
    }

    private function formData(): array
    {
        $endDate = trim((string) ($_POST['end_date'] ?? ''));
        return [
            'organizer_id' => Auth::id(),
            'room_id' => (int) ($_POST['room_id'] ?? 0),
            'schedule_title' => trim((string) ($_POST['schedule_title'] ?? '')),
            'schedule_description' => trim((string) ($_POST['schedule_description'] ?? '')) ?: null,
            'recurrence_type' => in_array($_POST['recurrence_type'] ?? '', Schedule::RECURRENCE_TYPES, true)
                ? $_POST['recurrence_type'] : 'one_time',
            'start_date' => (string) ($_POST['start_date'] ?? ''),
            'end_date' => $endDate !== '' ? $endDate : null,
            'start_time' => (string) ($_POST['start_time'] ?? ''),
            'end_time' => (string) ($_POST['end_time'] ?? ''),
            'schedule_status' => in_array($_POST['schedule_status'] ?? '', Schedule::STATUSES, true)
                ? $_POST['schedule_status'] : 'active',
            'days' => array_map('intval', (array) ($_POST['days'] ?? [])),
        ];
    }

    private function validate(array $data): ?string
    {
        if ($data['room_id'] <= 0 || $this->rooms->find($data['room_id']) === null) {
            return 'Choose a valid room.';
        }
        if ($data['schedule_title'] === '') {
            return 'A title is required.';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
            return 'Start date is required (YYYY-MM-DD).';
        }
        if ($data['end_date'] && $data['end_date'] < $data['start_date']) {
            return 'End date cannot be before the start date.';
        }
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['start_time'])
            || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $data['end_time'])) {
            return 'Start and end times are required (24-hour).';
        }
        if ($data['end_time'] <= $data['start_time']) {
            return 'End time must be later than start time.';
        }
        if ($data['recurrence_type'] === 'weekly' && empty($data['days'])) {
            return 'Weekly schedules need at least one weekday selected.';
        }
        if ($data['recurrence_type'] !== 'one_time' && empty($data['end_date'])) {
            return 'Recurring schedules need an end date (or pick one_time).';
        }
        return null;
    }
}
