<?php

namespace App\Http\Controllers\Management;

use App\Actions\Attendance\MarkAttendance;
use App\Enums\CheckInStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\AttendanceUpdateRequest;
use App\Models\CheckIn;
use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(private MarkAttendance $markAttendance) {}

    /**
     * Show the roll for one class.
     */
    public function edit(ClassSession $classSession): Response
    {
        $classSession->load(['teacher', 'checkIns.user']);

        $students = $classSession->checkIns
            ->sortBy(fn (CheckIn $checkIn): string => $checkIn->user->name)
            ->map(fn (CheckIn $checkIn): array => [
                'check_in_id' => $checkIn->id,
                'name' => $checkIn->user->name,
                'phone' => $checkIn->user->phone,
                'status' => $checkIn->status->value,
                'checked_in_at' => $checkIn->checked_in_at->format('d/m H:i'),
            ])
            ->values();

        return Inertia::render('management/classes/attendance', [
            'classSession' => [
                'id' => $classSession->id,
                'name' => $classSession->name,
                'date' => $classSession->date->format('d/m/Y'),
                'starts_at' => substr($classSession->start_time, 0, 5),
                'ends_at' => substr($classSession->end_time, 0, 5),
                'teacher' => $classSession->teacher->name,
                'max_students' => $classSession->max_students,
                'taken' => $classSession->takenSpots(),
            ],
            'students' => $students,
            'options' => collect(CheckInStatus::cases())
                ->map(fn (CheckInStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Record who was present.
     */
    public function update(AttendanceUpdateRequest $request, ClassSession $classSession): RedirectResponse
    {
        $changed = $this->markAttendance->handle($request->user(), $request->statuses());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $changed === 1
                ? __('Presença atualizada.')
                : __(':count presenças atualizadas.', ['count' => $changed]),
        ]);

        return back();
    }
}
