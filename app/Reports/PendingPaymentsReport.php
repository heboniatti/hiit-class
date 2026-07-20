<?php

namespace App\Reports;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Reports\Concerns\QueriesCheckIns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;

class PendingPaymentsReport implements Report
{
    use QueriesCheckIns;

    public function key(): string
    {
        return 'pending-payments';
    }

    public function name(): string
    {
        return 'Pagamentos pendentes';
    }

    public function description(): string
    {
        return 'Mensalidades em aberto, com o quanto já passou do vencimento.';
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Aluno', 'Telefone', 'Valor', 'Vencimento', 'Situação', 'Dias em atraso'];
    }

    /**
     * @return array<int, array<int, string|int|float>>
     */
    public function rows(ReportFilters $filters): array
    {
        $today = Date::today();

        return Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->with('user')
            ->when($filters->studentId, fn (Builder $query, int $id) => $query->where('user_id', $id))
            ->when($filters->planId, fn (Builder $query, int $id) => $query
                ->whereIn('user_id', $this->studentIdsOnPlan($id)))
            ->orderBy('due_date')
            ->get()
            ->map(fn (Payment $payment): array => [
                $payment->user->name,
                $payment->user->phone,
                number_format((float) $payment->amount, 2, ',', '.'),
                $payment->due_date->format('d/m/Y'),
                // The derived state, so the file says the same as the screen.
                $payment->statusLabel(),
                $payment->isOverdue() ? (int) $payment->due_date->diffInDays($today) : 0,
            ])
            ->values()
            ->all();
    }
}
