<?php

namespace App\Http\Controllers\Student;

use App\Enums\CheckInStatus;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Support\StudentPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    public function __construct(private StudentPresenter $presenter) {}

    /**
     * Show the classes the student has been to.
     */
    public function __invoke(Request $request): Response
    {
        $student = $request->user();

        $history = $student->checkIns()
            ->with('classSession')
            // Qualified: class_sessions also has a "status" column.
            ->whereNot('check_ins.status', CheckInStatus::CheckedIn)
            ->join('class_sessions', 'class_sessions.id', '=', 'check_ins.class_session_id')
            ->orderByDesc('class_sessions.date')
            ->orderByDesc('class_sessions.start_time')
            ->select('check_ins.*')
            ->limit(50)
            ->get();

        return Inertia::render('student/history', [
            'history' => $history
                ->map(fn (CheckIn $checkIn): array => $this->presenter->historyRow($checkIn))
                ->values(),
            'attendance' => $this->presenter->recentAttendance($student),
        ]);
    }
}
