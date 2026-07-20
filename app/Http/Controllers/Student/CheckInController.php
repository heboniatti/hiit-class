<?php

namespace App\Http\Controllers\Student;

use App\Actions\CheckIns\CancelCheckIn;
use App\Actions\CheckIns\PerformCheckIn;
use App\Exceptions\CheckInDeniedException;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckInController extends Controller
{
    public function __construct(
        private PerformCheckIn $performCheckIn,
        private CancelCheckIn $cancelCheckIn,
    ) {}

    /**
     * Reserve the student a seat.
     */
    public function store(Request $request, ClassSession $classSession): RedirectResponse
    {
        try {
            $this->performCheckIn->handle($request->user(), $classSession);
        } catch (CheckInDeniedException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Check-in realizado com sucesso! Sua vaga está reservada.'),
        ]);

        return back();
    }

    /**
     * Give the student's seat back to the class.
     */
    public function destroy(Request $request, ClassSession $classSession): RedirectResponse
    {
        try {
            $this->cancelCheckIn->handle($request->user(), $classSession);
        } catch (CheckInDeniedException $exception) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);

            return back();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Check-in cancelado. Sua vaga foi liberada.'),
        ]);

        return back();
    }
}
