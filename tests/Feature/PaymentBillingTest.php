<?php

use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-07-20 08:00:00'));

test('the command generates one monthly fee for an enrollment active at the turn of the month', function () {
    $student = User::factory()->student()->create();
    $plan = Plan::factory()->weekly(3)->create([
        'price' => '159.90',
        'payment_due_day' => 7,
    ]);
    $enrollment = StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => $plan->id,
        'started_at' => '2026-06-15',
    ]);

    $this->artisan('payments:generate')
        ->expectsOutput('1 mensalidades geradas.')
        ->assertSuccessful();

    $payment = Payment::sole();

    expect($payment->user_id)->toBe($student->id)
        ->and($payment->student_plan_id)->toBe($enrollment->id)
        ->and($payment->amount)->toBe('159.90')
        ->and($payment->billing_period->toDateString())->toBe('2026-07-01')
        ->and($payment->due_date->toDateString())->toBe('2026-07-07')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->recorded_by)->toBeNull();

    $audit = AuditLog::query()->where('action', 'payment.generated')->sole();

    expect($audit->user_id)->toBeNull()
        ->and($audit->new_values['billing_period'])->toBe('2026-07-01');

    $this->artisan('payments:generate')
        ->expectsOutput('0 mensalidades geradas.')
        ->assertSuccessful();

    expect(Payment::count())->toBe(1);
});

test('an enrollment linked during the current month waits for the next turn of the month', function () {
    $enrollment = StudentPlan::factory()->create([
        'started_at' => '2026-07-01',
    ]);

    $this->artisan('payments:generate')->assertSuccessful();

    expect(Payment::count())->toBe(0);

    Carbon::setTestNow('2026-08-01 00:05:00');

    $this->artisan('payments:generate')->assertSuccessful();

    expect(Payment::sole()->student_plan_id)->toBe($enrollment->id);
});

test('trial and already ended enrollments are not billed', function () {
    StudentPlan::factory()->create([
        'plan_id' => Plan::factory()->trial(),
        'started_at' => '2026-06-01',
    ]);

    StudentPlan::factory()->create([
        'started_at' => '2026-05-01',
        'ended_at' => '2026-06-30',
    ]);

    $this->artisan('payments:generate')->assertSuccessful();

    expect(Payment::count())->toBe(0);
});

test('a manual fee in the current month prevents a duplicate automatic fee', function () {
    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'started_at' => '2026-06-01',
    ]);
    $manualPayment = Payment::factory()->create([
        'user_id' => $student->id,
        'due_date' => '2026-07-10',
        'billing_period' => null,
    ]);

    $this->artisan('payments:generate')->assertSuccessful();

    expect(Payment::count())->toBe(1)
        ->and(Payment::sole()->is($manualPayment))->toBeTrue();
});
