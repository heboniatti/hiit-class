<?php

namespace App\Http\Controllers;

use App\Enums\ClassSessionStatus;
use App\Models\ClassSession;
use App\Support\MotivationalMessages;
use App\Support\StudentPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private StudentPresenter $presenter,
        private MotivationalMessages $messages,
    ) {}

    /**
     * Send each role to the home screen built for them.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->role->managesStudents()) {
            return to_route('management.dashboard');
        }

        return $this->studentDashboard($request);
    }

    /**
     * Build the student's home screen.
     *
     * The single most important thing on it is the check-in button, so the next
     * class the student can actually book comes first.
     */
    private function studentDashboard(Request $request): Response
    {
        $student = $request->user();

        $upcoming = ClassSession::query()
            ->with(['teacher', 'checkIns'])
            ->where('status', ClassSessionStatus::Scheduled)
            ->whereDate('date', '>=', Date::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(10)
            ->get();

        $cards = $upcoming
            ->map(fn (ClassSession $class): array => $this->presenter->classCard($student, $class))
            ->values();

        // Prefer the next class the student already holds a seat in, otherwise
        // the soonest one they could still book.
        $nextClass = $cards->firstWhere('is_checked_in', true)
            ?? $cards->firstWhere('can_check_in', true)
            ?? $cards->first();

        return Inertia::render('student/dashboard', [
            'greeting' => $this->messages->greeting($student),
            'message' => $this->messages->forToday($student),
            'nextClass' => $nextClass,
            'plan' => $this->presenter->planSummary($student),
            'attendance' => $this->presenter->recentAttendance($student),
        ]);
    }
}
