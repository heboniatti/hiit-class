<?php

use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
});

test('a student attaches a receipt without settling the payment', function () {
    $student = User::factory()->student()->create();
    $payment = Payment::factory()->overdue()->create(['user_id' => $student->id]);

    $this->actingAs($student)->post(route('payment-receipts.store', $payment), [
        'receipt' => UploadedFile::fake()->create('comprovante.pdf', 100, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    $payment->refresh();

    Storage::disk('local')->assertExists($payment->receipt_path);

    expect($payment->receipt_original_name)->toBe('comprovante.pdf')
        ->and($payment->receipt_uploaded_at)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->paid_at)->toBeNull()
        ->and($payment->recorded_by)->toBeNull();

    $audit = AuditLog::query()->where('action', 'payment.receipt_uploaded')->sole();

    expect($audit->user_id)->toBe($student->id);

    $this->actingAs($student)->get(route('my-plan'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.0.has_receipt', true)
            ->where('payments.0.is_settled', false)
            ->has('payments.0.receipt_uploaded_at'),
        );
});

test('a student can replace a receipt while the payment is pending', function () {
    $student = User::factory()->student()->create();
    $payment = Payment::factory()->create(['user_id' => $student->id]);

    $this->actingAs($student)->post(route('payment-receipts.store', $payment), [
        'receipt' => UploadedFile::fake()->image('primeiro.jpg'),
    ]);

    $oldPath = $payment->fresh()->receipt_path;

    $this->actingAs($student)->post(route('payment-receipts.store', $payment), [
        'receipt' => UploadedFile::fake()->image('segundo.png'),
    ])->assertSessionHasNoErrors();

    $payment->refresh();

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($payment->receipt_path);

    expect($payment->receipt_original_name)->toBe('segundo.png')
        ->and(AuditLog::query()->where('action', 'payment.receipt_replaced')->count())->toBe(1);
});

test('receipt uploads validate file type and size', function () {
    $student = User::factory()->student()->create();
    $payment = Payment::factory()->create(['user_id' => $student->id]);

    $this->actingAs($student)
        ->from(route('my-plan'))
        ->post(route('payment-receipts.store', $payment), [
            'receipt' => UploadedFile::fake()->create('arquivo.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('receipt');

    $this->actingAs($student)
        ->from(route('my-plan'))
        ->post(route('payment-receipts.store', $payment), [
            'receipt' => UploadedFile::fake()->create('grande.pdf', 5121, 'application/pdf'),
        ])->assertSessionHasErrors('receipt');
});

test('other students and settled payments can not receive an upload', function () {
    $student = User::factory()->student()->create();
    $otherStudent = User::factory()->student()->create();
    $pending = Payment::factory()->create(['user_id' => $student->id]);
    $paid = Payment::factory()->paid()->create(['user_id' => $student->id]);

    $this->actingAs($otherStudent)->post(route('payment-receipts.store', $pending), [
        'receipt' => UploadedFile::fake()->create('comprovante.pdf', 10, 'application/pdf'),
    ])->assertForbidden();

    $this->actingAs($student)->post(route('payment-receipts.store', $paid), [
        'receipt' => UploadedFile::fake()->create('comprovante.pdf', 10, 'application/pdf'),
    ])->assertForbidden();
});

test('only the student who owns the receipt and staff may download it', function () {
    $student = User::factory()->student()->create();
    $otherStudent = User::factory()->student()->create();
    $teacher = User::factory()->teacher()->create();
    $payment = Payment::factory()->create([
        'user_id' => $student->id,
        'receipt_path' => 'payment-receipts/comprovante.pdf',
        'receipt_original_name' => 'comprovante.pdf',
    ]);
    Storage::disk('local')->put($payment->receipt_path, 'arquivo');

    $this->actingAs($student)
        ->get(route('payment-receipts.show', $payment))
        ->assertDownload('comprovante.pdf');

    $this->actingAs($teacher)
        ->get(route('payment-receipts.show', $payment))
        ->assertDownload('comprovante.pdf');

    $this->actingAs($otherStudent)
        ->get(route('payment-receipts.show', $payment))
        ->assertForbidden();
});

test('the management payment list identifies automatic fees with receipts', function () {
    $teacher = User::factory()->teacher()->create();
    $payment = Payment::factory()->create([
        'billing_period' => '2026-07-01',
        'receipt_path' => 'payment-receipts/comprovante.pdf',
        'receipt_original_name' => 'comprovante.pdf',
        'receipt_uploaded_at' => now(),
    ]);

    $this->actingAs($teacher)
        ->get(route('management.payments.index', ['filter' => 'all']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('payments.0.id', $payment->id)
            ->where('payments.0.is_automatic', true)
            ->where('payments.0.has_receipt', true)
            ->has('payments.0.receipt_uploaded_at'),
        );
});
