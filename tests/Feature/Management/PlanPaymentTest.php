<?php

use App\Actions\CheckIns\PerformCheckIn;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlanType;
use App\Exceptions\CheckInDeniedException;
use App\Models\AuditLog;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

function admin(): User
{
    return User::factory()->admin()->create();
}

test('a teacher creates a weekly plan', function () {
    $staff = admin();

    $this->actingAs($staff)->post(route('management.plans.store'), [
        'name' => 'Plano 4x por semana',
        'type' => PlanType::Weekly->value,
        'checkins_per_week' => 4,
        'max_students' => 30,
        'price' => '180.00',
        'payment_due_day' => 5,
        'allows_rollover' => '1',
        'is_active' => '1',
    ])->assertSessionHasNoErrors();

    $plan = Plan::firstWhere('name', 'Plano 4x por semana');

    expect($plan->type)->toBe(PlanType::Weekly)
        ->and($plan->checkins_per_week)->toBe(4)
        ->and($plan->max_students)->toBe(30)
        ->and($plan->allows_rollover)->toBeTrue()
        ->and($plan->is_active)->toBeTrue();
});

test('an empty quota means unlimited', function () {
    $staff = admin();

    $this->actingAs($staff)->post(route('management.plans.store'), [
        'name' => 'Plano ilimitado',
        'type' => PlanType::Weekly->value,
        'checkins_per_week' => '',
        'price' => '250',
        'payment_due_day' => 10,
    ])->assertSessionHasNoErrors();

    $plan = Plan::firstWhere('name', 'Plano ilimitado');

    expect($plan->checkins_per_week)->toBeNull()
        ->and($plan->hasUnlimitedCheckIns())->toBeTrue()
        ->and($plan->allowanceLabel())->toBe('Check-ins ilimitados');
});

test('an empty student limit means unlimited capacity', function () {
    $staff = admin();

    $this->actingAs($staff)->post(route('management.plans.store'), [
        'name' => 'Plano sem lotação',
        'type' => PlanType::Weekly->value,
        'checkins_per_week' => 3,
        'max_students' => '',
        'price' => '180',
        'payment_due_day' => 10,
    ])->assertSessionHasNoErrors();

    $plan = Plan::firstWhere('name', 'Plano sem lotação');

    expect($plan->max_students)->toBeNull()
        ->and($plan->hasUnlimitedStudents())->toBeTrue()
        ->and($plan->isAtCapacity())->toBeFalse();
});

test('a trial plan discards any weekly quota typed into it', function () {
    $staff = admin();

    $this->actingAs($staff)->post(route('management.plans.store'), [
        'name' => 'Experimental',
        'type' => PlanType::Trial->value,
        // Nonsense for a trial: it must not be stored and silently ignored.
        'checkins_per_week' => 5,
        'price' => '0',
        'payment_due_day' => 10,
    ])->assertSessionHasNoErrors();

    expect(Plan::firstWhere('name', 'Experimental')->checkins_per_week)->toBeNull();
});

test('the due day is capped at 28 so it exists in February', function () {
    $staff = admin();

    $this->actingAs($staff)
        ->from(route('management.plans.index'))
        ->post(route('management.plans.store'), [
            'name' => 'Plano',
            'type' => PlanType::Weekly->value,
            'price' => '100',
            'payment_due_day' => 31,
        ])->assertSessionHasErrors('payment_due_day');
});

test('the plan list shows how many students are on each plan', function () {
    $staff = admin();
    $plan = Plan::factory()->weekly(3)->create();
    StudentPlan::factory()->count(2)->create(['plan_id' => $plan->id]);
    // An ended enrollment must not be counted.
    StudentPlan::factory()->ended()->create(['plan_id' => $plan->id]);

    $this->actingAs($staff)->get(route('management.plans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/plans/index')
            ->where('plans.0.active_students', 2),
        );
});

test('the plan list shows capacity and whether it is full', function () {
    $staff = admin();
    $plan = Plan::factory()->weekly(3)->create(['max_students' => 2]);
    StudentPlan::factory()->count(2)->create(['plan_id' => $plan->id]);

    $this->actingAs($staff)->get(route('management.plans.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plans.0.max_students', 2)
            ->where('plans.0.active_students', 2)
            ->where('plans.0.is_full', true),
        );
});

test('a plan limit can not be reduced below its active students', function () {
    $staff = admin();
    $plan = Plan::factory()->weekly(3)->create(['max_students' => 10]);
    StudentPlan::factory()->count(3)->create(['plan_id' => $plan->id]);

    $this->actingAs($staff)
        ->from(route('management.plans.index'))
        ->put(route('management.plans.update', $plan), [
            'name' => $plan->name,
            'type' => $plan->type->value,
            'checkins_per_week' => $plan->checkins_per_week,
            'max_students' => 2,
            'price' => $plan->price,
            'payment_due_day' => $plan->payment_due_day,
            'is_active' => '1',
        ])->assertSessionHasErrors('max_students');

    expect($plan->fresh()->max_students)->toBe(10);
});

test('a full plan can not be assigned to a new student', function () {
    $staff = admin();
    $plan = Plan::factory()->weekly(3)->create(['max_students' => 1]);
    StudentPlan::factory()->create(['plan_id' => $plan->id]);

    $this->actingAs($staff)
        ->from(route('management.students.create'))
        ->post(route('management.students.store'), [
            'name' => 'Aluno sem vaga',
            'phone' => '11912344321',
            'status' => 'active',
            'plan_id' => $plan->id,
            'password' => '123456',
            'password_confirmation' => '123456',
        ])->assertSessionHasErrors('plan_id');

    expect(User::query()->where('phone', '11912344321')->exists())->toBeFalse();
});

test('a failed switch to a full plan keeps the student on the original plan', function () {
    $staff = admin();
    $originalPlan = Plan::factory()->weekly(3)->create();
    $fullPlan = Plan::factory()->weekly(3)->create(['max_students' => 1]);
    $student = User::factory()->student()->create(['name' => 'Nome original']);
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => $originalPlan->id,
    ]);
    StudentPlan::factory()->create(['plan_id' => $fullPlan->id]);

    $this->actingAs($staff)
        ->from(route('management.students.show', $student))
        ->put(route('management.students.update', $student), [
            'name' => 'Nome alterado',
            'phone' => $student->phone,
            'status' => 'active',
            'plan_id' => $fullPlan->id,
        ])->assertSessionHasErrors('plan_id');

    expect($student->fresh()->name)->toBe('Nome original')
        ->and($student->fresh()->currentStudentPlan?->plan_id)->toBe($originalPlan->id);
});

test('full plans are disabled for new assignments but remain visible to their current students', function () {
    $staff = admin();
    $plan = Plan::factory()->weekly(3)->create([
        'name' => 'Plano lotado',
        'max_students' => 1,
    ]);
    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => $plan->id,
    ]);

    $this->actingAs($staff)->get(route('management.students.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plans.0.disabled', true)
            ->where('plans.0.label', fn (string $label): bool => str_contains($label, '1/1 alunos')),
        );

    $this->actingAs($staff)->get(route('management.students.show', $student))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plans.0.disabled', false),
        );
});

test('registering a fee records who lodged it', function () {
    $staff = admin();
    $student = User::factory()->student()->create();

    $this->actingAs($staff)->post(route('management.payments.store'), [
        'user_id' => $student->id,
        'amount' => '160.00',
        'due_date' => Carbon::today()->addDays(10)->toDateString(),
        'status' => PaymentStatus::Pending->value,
    ])->assertSessionHasNoErrors();

    $payment = Payment::firstWhere('user_id', $student->id);

    expect($payment->recorded_by)->toBe($staff->id);

    $log = AuditLog::query()->where('action', 'payment.created')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($staff->id)
        ->and($log->auditable_id)->toBe($payment->id);
});

test('marking a fee paid unblocks the student and leaves a trail', function () {
    $staff = admin();
    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);
    $payment = Payment::factory()->overdue()->create(['user_id' => $student->id]);

    $class = ClassSession::factory()->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);

    // Blocked while the fee is overdue.
    expect(fn () => app(PerformCheckIn::class)->handle($student, $class))
        ->toThrow(CheckInDeniedException::class);

    $this->actingAs($staff)
        ->from(route('management.payments.index'))
        ->put(route('management.payments.pay', $payment), [
            'method' => PaymentMethod::Pix->value,
        ])->assertSessionHasNoErrors();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->method)->toBe(PaymentMethod::Pix)
        ->and($payment->recorded_by)->toBe($staff->id)
        ->and($payment->isOverdue())->toBeFalse();

    // And now the student can train.
    $checkIn = app(PerformCheckIn::class)->handle($student, $class);
    expect($checkIn)->not->toBeNull();

    $log = AuditLog::query()->where('action', 'payment.updated')->latest('id')->first();

    expect($log->user_id)->toBe($staff->id)
        ->and($log->old_values['status'])->toBe('pending')
        ->and($log->new_values['status'])->toBe('paid');
});

test('every change to a fee is traceable to a person', function () {
    $first = admin();
    $second = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    $payment = Payment::factory()->create(['user_id' => $student->id]);

    $this->actingAs($first)->put(route('management.payments.update', $payment), [
        'user_id' => $student->id,
        'amount' => '200.00',
        'due_date' => Carbon::today()->toDateString(),
        'status' => PaymentStatus::Pending->value,
    ]);

    $this->actingAs($second)->put(route('management.payments.pay', $payment), [
        'method' => PaymentMethod::Cash->value,
    ]);

    $logs = AuditLog::query()->where('action', 'payment.updated')->orderBy('id')->get();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]->user_id)->toBe($first->id)
        ->and($logs[1]->user_id)->toBe($second->id)
        // recorded_by holds the latest hand; the log holds every one before it.
        ->and($payment->fresh()->recorded_by)->toBe($second->id);
});

test('the trail is readable rather than encoded', function () {
    $staff = admin();
    $student = User::factory()->student()->create();
    $payment = Payment::factory()->create([
        'user_id' => $student->id,
        'due_date' => '2026-07-11',
    ]);

    $this->actingAs($staff)->put(route('management.payments.pay', $payment), [
        'method' => PaymentMethod::Pix->value,
    ]);

    $log = AuditLog::query()->where('action', 'payment.updated')->latest('id')->first();

    // Plain dates, not escaped JSON timestamps: someone has to read this later.
    expect($log->old_values['due_date'])->toBe('2026-07-11')
        ->and($log->new_values['paid_at'])->toBe('2026-07-13')
        ->and($log->new_values['method'])->toBe('pix')
        ->and($log->old_values['paid_at'])->toBeNull();
});

test('a fee can not be raised against a member of staff', function () {
    $staff = admin();
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($staff)
        ->from(route('management.payments.index'))
        ->post(route('management.payments.store'), [
            'user_id' => $teacher->id,
            'amount' => '100',
            'due_date' => Carbon::today()->toDateString(),
            'status' => PaymentStatus::Pending->value,
        ])->assertSessionHasErrors('user_id');
});

test('the payment filters split overdue from paid', function () {
    $staff = admin();
    Payment::factory()->overdue()->create();
    Payment::factory()->paid()->create();

    $this->actingAs($staff)->get(route('management.payments.index', ['filter' => 'overdue']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments', 1)
            ->where('payments.0.is_overdue', true)
            ->where('payments.0.status', 'Vencido')
            ->where('counts.overdue', 1),
        );

    $this->actingAs($staff)->get(route('management.payments.index', ['filter' => 'paid']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('payments', 1)
            ->where('payments.0.is_settled', true),
        );
});

test('students can not reach plans or payments', function () {
    $student = User::factory()->student()->create();
    $payment = Payment::factory()->create(['user_id' => $student->id]);

    $this->actingAs($student)->get(route('management.plans.index'))->assertForbidden();
    $this->actingAs($student)->get(route('management.payments.index'))->assertForbidden();
    $this->actingAs($student)->put(route('management.payments.pay', $payment), [
        'method' => PaymentMethod::Pix->value,
    ])->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});
