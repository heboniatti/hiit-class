<?php

namespace App\Actions\Payments;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UploadPaymentReceipt
{
    /**
     * Store or replace the receipt attached to a pending payment.
     */
    public function handle(User $student, Payment $payment, UploadedFile $receipt): Payment
    {
        $newPath = $receipt->store('payment-receipts', 'local');

        if ($newPath === false) {
            throw new RuntimeException('Não foi possível armazenar o comprovante.');
        }

        $oldPath = $payment->receipt_path;
        $oldName = $payment->receipt_original_name;

        try {
            DB::transaction(function () use ($student, $payment, $receipt, $newPath, $oldName): void {
                $payment->update([
                    'receipt_path' => $newPath,
                    'receipt_original_name' => $receipt->getClientOriginalName(),
                    'receipt_uploaded_at' => Date::now(),
                ]);

                AuditLog::create([
                    'auditable_type' => $payment->getMorphClass(),
                    'auditable_id' => $payment->id,
                    'user_id' => $student->id,
                    'action' => $oldName === null
                        ? 'payment.receipt_uploaded'
                        : 'payment.receipt_replaced',
                    'old_values' => $oldName === null ? null : ['receipt_original_name' => $oldName],
                    'new_values' => ['receipt_original_name' => $receipt->getClientOriginalName()],
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($newPath);

            throw $exception;
        }

        if ($oldPath !== null && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $payment->refresh();
    }
}
