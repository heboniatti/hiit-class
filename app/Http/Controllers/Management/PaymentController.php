<?php

namespace App\Http\Controllers\Management;

use App\Actions\Payments\RecordPayment;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\PaymentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private RecordPayment $recordPayment) {}

    /**
     * List the fees, newest due date first.
     */
    public function index(Request $request): Response
    {
        $filter = (string) $request->query('filter', 'overdue');

        $payments = Payment::query()
            ->with(['user', 'recordedBy'])
            ->when($filter === 'overdue', fn ($query) => $query->overdue())
            ->when($filter === 'pending', fn ($query) => $query->where('status', PaymentStatus::Pending))
            ->when($filter === 'paid', fn ($query) => $query->where('status', PaymentStatus::Paid))
            ->orderByDesc('due_date')
            ->limit(100)
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'student' => $payment->user->name,
                'student_id' => $payment->user_id,
                'amount' => number_format((float) $payment->amount, 2, ',', '.'),
                'due_date' => $payment->due_date->format('d/m/Y'),
                'paid_at' => $payment->paid_at?->format('d/m/Y'),
                'method' => $payment->method?->label(),
                'status' => $payment->statusLabel(),
                'is_overdue' => $payment->isOverdue(),
                'is_settled' => $payment->status->isSettled(),
                'notes' => $payment->notes,
                'is_automatic' => $payment->billing_period !== null,
                'has_receipt' => $payment->receipt_path !== null,
                'receipt_uploaded_at' => $payment->receipt_uploaded_at?->format('d/m/Y H:i'),
                // Who last touched it, so the trail is visible and not just stored.
                'recorded_by' => $payment->recordedBy?->name,
            ])
            ->values();

        return Inertia::render('management/payments/index', [
            'payments' => $payments,
            'filter' => $filter,
            'students' => $this->studentOptions(),
            'methods' => collect(PaymentMethod::cases())
                ->map(fn (PaymentMethod $method): array => [
                    'value' => $method->value,
                    'label' => $method->label(),
                ])
                ->values(),
            'statuses' => collect(PaymentStatus::cases())
                ->map(fn (PaymentStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values(),
            'counts' => [
                'overdue' => Payment::query()->overdue()->count(),
                'pending' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
            ],
        ]);
    }

    /**
     * Raise a fee for a student.
     */
    public function store(PaymentRequest $request): RedirectResponse
    {
        $this->recordPayment->create($request->user(), $request->paymentAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mensalidade registrada.')]);

        return back();
    }

    /**
     * Change a fee.
     */
    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->recordPayment->update($request->user(), $payment, $request->paymentAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Mensalidade atualizada.')]);

        return back();
    }

    /**
     * Settle a fee, which unblocks the student's check-ins.
     */
    public function markAsPaid(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate(
            ['method' => ['required', Rule::enum(PaymentMethod::class)]],
            ['method.required' => 'Informe a forma de pagamento.'],
        );

        $this->recordPayment->markAsPaid(
            $request->user(),
            $payment,
            PaymentMethod::from($validated['method']),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Pagamento registrado. O aluno já pode fazer check-in.'),
        ]);

        return back();
    }

    /**
     * Get the students a fee can be raised for.
     *
     * @return array<int, array{value: int, label: string, due_day: int|null, price: float|null}>
     */
    private function studentOptions(): array
    {
        return User::query()
            ->where('role', UserRole::Student)
            ->with('currentStudentPlan.plan')
            ->orderBy('name')
            ->get()
            ->map(fn (User $student): array => [
                'value' => $student->id,
                'label' => $student->name,
                // Prefilled into the form so the teacher rarely types anything.
                'due_day' => $student->currentStudentPlan?->paymentDueDay(),
                'price' => $student->currentStudentPlan
                    ? (float) $student->currentStudentPlan->plan->price
                    : null,
            ])
            ->values()
            ->all();
    }
}
