<?php

namespace App\Http\Controllers;

use App\Actions\Payments\UploadPaymentReceipt;
use App\Enums\UserRole;
use App\Http\Requests\Student\PaymentReceiptRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReceiptController extends Controller
{
    /**
     * Attach a receipt submitted by the student who owes the payment.
     */
    public function store(
        PaymentReceiptRequest $request,
        Payment $payment,
        UploadPaymentReceipt $uploadPaymentReceipt,
    ): RedirectResponse {
        $uploadPaymentReceipt->handle(
            $request->user(),
            $payment,
            $request->file('receipt'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Comprovante enviado para conferência.'),
        ]);

        return back();
    }

    /**
     * Download a receipt without making private storage publicly accessible.
     */
    public function show(Request $request, Payment $payment): StreamedResponse
    {
        $user = $request->user();
        $mayView = $payment->user_id === $user->id
            || $user->hasRole(UserRole::Teacher, UserRole::Admin);

        abort_unless($mayView, 403);
        abort_if($payment->receipt_path === null, 404);
        abort_unless(Storage::disk('local')->exists($payment->receipt_path), 404);

        $downloadName = Str::ascii(basename($payment->receipt_original_name ?? 'comprovante'));

        return Storage::disk('local')->download($payment->receipt_path, $downloadName);
    }
}
