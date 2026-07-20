<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\StudentPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyPlanController extends Controller
{
    public function __construct(private StudentPresenter $presenter) {}

    /**
     * Show the student their plan and what they owe.
     *
     * Read only: a student never edits financial information.
     */
    public function __invoke(Request $request): Response
    {
        $student = $request->user();

        $payments = $student->payments()
            ->orderByDesc('due_date')
            ->limit(12)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'amount' => number_format((float) $payment->amount, 2, ',', '.'),
                'due_date' => $payment->due_date->format('d/m/Y'),
                'paid_at' => $payment->paid_at?->format('d/m/Y'),
                'status' => $payment->statusLabel(),
                'is_overdue' => $payment->isOverdue(),
                'is_settled' => $payment->status->isSettled(),
                'has_receipt' => $payment->receipt_path !== null,
                'receipt_uploaded_at' => $payment->receipt_uploaded_at?->format('d/m/Y H:i'),
            ])
            ->values();

        return Inertia::render('student/plan', [
            'plan' => $this->presenter->planSummary($student),
            'payments' => $payments,
        ]);
    }
}
