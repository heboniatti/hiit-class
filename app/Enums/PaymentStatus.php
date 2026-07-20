<?php

namespace App\Enums;

/**
 * The persisted state of a payment.
 *
 * "Vencido" is intentionally not a case: it is derived from Pending plus a due
 * date in the past, which keeps a single source of truth and removes the need
 * for a daily job. See Payment::scopeOverdue().
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Exempt = 'exempt';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Exempt => 'Isento',
        };
    }

    /**
     * Determine whether the payment can no longer become overdue.
     */
    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Exempt], true);
    }
}
