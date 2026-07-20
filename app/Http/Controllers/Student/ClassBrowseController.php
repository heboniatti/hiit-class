<?php

namespace App\Http\Controllers\Student;

use App\Enums\ClassSessionStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Support\StudentPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

class ClassBrowseController extends Controller
{
    public function __construct(private StudentPresenter $presenter) {}

    /**
     * List the classes the student can still join.
     */
    public function index(Request $request): Response
    {
        $student = $request->user();

        $classes = ClassSession::query()
            ->with(['teacher', 'checkIns'])
            ->whereIn('status', [ClassSessionStatus::Scheduled, ClassSessionStatus::Cancelled])
            ->whereDate('date', '>=', Date::today())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(30)
            ->get();

        return Inertia::render('student/classes/index', [
            'classes' => $classes
                ->map(fn (ClassSession $class): array => $this->presenter->classCard($student, $class))
                ->values(),
            'plan' => $this->presenter->planSummary($student),
        ]);
    }

    /**
     * Show one class, where the student checks in.
     */
    public function show(Request $request, ClassSession $classSession): Response
    {
        $classSession->load(['teacher', 'checkIns']);

        return Inertia::render('student/classes/show', [
            'classSession' => $this->presenter->classCard($request->user(), $classSession),
            'plan' => $this->presenter->planSummary($request->user()),
        ]);
    }
}
