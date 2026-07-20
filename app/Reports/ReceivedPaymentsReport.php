<?php

namespace App\Reports;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Reports\Concerns\QueriesCheckIns;
use Illuminate\Database\Eloquent\Builder;

class ReceivedPaymentsReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'received-payments';
    }

    public function name(): string
    {
        return 'Pagamentos recebidos';
    }

    public function description(): string
    {
        return 'Mensalidades pagas no período, com forma de pagamento e quem lançou.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Data do pagamento', 'Aluno', 'Valor', 'Forma', 'Vencimento', 'Lançado por'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        return Payment::query()
            ->where('status', PaymentStatus::Paid)
            // Filtered by when the money came in, not when it was due.
            ->whereDate('paid_at', '>=', $filters->from)
            ->whereDate('paid_at', '<=', $filters->to)
            ->with(['user', 'recordedBy'])
            ->when($filters->studentId, fn (Builder $query, int $id) => $query->where('user_id', $id))
            ->when($filters->planId, fn (Builder $query, int $id) => $query
                ->whereIn('user_id', $this->studentIdsOnPlan($id)))
            ->orderBy('paid_at')
            ->get()
            ->map(fn (Payment $payment): array => [
                $payment->paid_at?->format('d/m/Y') ?? '—',
                $payment->user->name,
                number_format((float) $payment->amount, 2, ',', '.'),
                $payment->method?->label() ?? '—',
                $payment->due_date->format('d/m/Y'),
                // Branch on the foreign key, not on the relation: recorded_by
                // is nullable, but the BelongsTo generic says User, so PHPStan
                // reads "?->" as redundant — and dropping it is a 500 the first
                // time a payment has nobody attached.
                $payment->recorded_by === null ? '—' : $payment->recordedBy->name,
            ])
            ->values()
            ->all();
    }
}
