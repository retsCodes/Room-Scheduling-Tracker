<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\ScheduleOccurrence;

final class OccurrenceController
{
    public function __construct(private ScheduleOccurrence $occurrences) {}

    public function index(): void
    {
        Auth::requireLogin();
        $from = $_GET['from'] ?? date('Y-m-d');
        $to = $_GET['to'] ?? date('Y-m-d', strtotime('+30 days'));
        $roomId = isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int) $_GET['room_id'] : null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d', strtotime('+30 days'));
        view('occurrences/index', [
            'title' => 'Occurrences',
            'occurrences' => $this->occurrences->forDateRange($from, $to, $roomId),
            'from' => $from, 'to' => $to, 'room_id' => $roomId,
        ]);
    }

    public function setStatus(int $id): void
    {
        Auth::requireCanBook();
        if (!is_post() || !verify_csrf()) {
            flash('error', 'Invalid request.');
            redirect('occurrences');
        }
        $status = in_array($_POST['status'] ?? '', ScheduleOccurrence::STATUSES, true)
            ? $_POST['status'] : 'scheduled';
        $occ = $this->occurrences->find($id);
        if ($occ === null) {
            flash('error', 'Occurrence not found.');
            redirect('occurrences');
        }
        $this->occurrences->setStatus($id, $status);
        flash('success', 'Occurrence marked as ' . $status . '.');
        redirect('schedules/' . (int) $occ['schedule_id']);
    }
}
