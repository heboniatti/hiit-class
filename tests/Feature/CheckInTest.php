<?php

use App\Actions\CheckIns\CancelCheckIn;
use App\Actions\CheckIns\PerformCheckIn;
use App\Enums\CheckInStatus;
use App\Enums\UserStatus;
use App\Exceptions\CheckInDeniedException;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

// A fixed Monday morning. Freezing the clock keeps the check-in window and the
// cancellation deadline deterministic instead of depending on the hour the
// suite happens to run.
beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

/**
 * Build a student on an active weekly plan with no money owed.
 */
function activeStudent(int $checkInsPerWeek = 3): User
{
    $student = User::factory()->student()->create();

    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly($checkInsPerWeek),
    ]);

    return $student;
}

/**
 * Build a class this evening: inside the check-in window and still cancellable.
 */
function openClass(int $maxStudents = 20): ClassSession
{
    return ClassSession::factory()->withCapacity($maxStudents)->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);
}

function checkIn(User $student, ClassSession $class): CheckIn
{
    return app(PerformCheckIn::class)->handle($student, $class);
}

test('a student checks in and reserves a seat', function () {
    $student = activeStudent();
    $class = openClass();

    $checkIn = checkIn($student, $class);

    expect($checkIn->status)->toBe(CheckInStatus::CheckedIn)
        ->and($checkIn->checked_in_at)->not->toBeNull()
        ->and($class->fresh()->takenSpots())->toBe(1);
});

test('checking in spends one of the weekly check-ins', function () {
    $student = activeStudent(3);

    expect(availableFor($student))->toBe(3);

    checkIn($student, openClass());

    expect(availableFor($student))->toBe(2);
});

test('a deactivated student can not check in', function () {
    $student = activeStudent();
    $student->update(['status' => UserStatus::Inactive]);

    checkIn($student, openClass());
})->throws(CheckInDeniedException::class, 'Seu cadastro está inativo. Procure o professor.');

test('a student with no plan can not check in', function () {
    $student = User::factory()->student()->create();

    checkIn($student, openClass());
})->throws(CheckInDeniedException::class, 'Você não possui um plano ativo. Procure o professor.');

test('a student without a plan claims an experimental plan on the first check-in', function () {
    $trial = Plan::factory()->trial()->create(['name' => 'Experimental HIIT']);
    $student = User::factory()->student()->create();

    $firstCheckIn = checkIn($student, openClass());
    $studentPlan = $student->fresh()->currentStudentPlan()->with('plan')->firstOrFail();

    expect($firstCheckIn->status)->toBe(CheckInStatus::CheckedIn)
        ->and($studentPlan->plan_id)->toBe($trial->id)
        ->and(availableFor($student))->toBe(0);

    checkIn($student, openClass());
})->throws(
    CheckInDeniedException::class,
    'Você já usou seu check-in experimental. Procure o professor para escolher um plano.',
);

test('a full class does not consume the experimental enrollment', function () {
    Plan::factory()->trial()->create();
    $class = openClass(maxStudents: 1);
    checkIn(activeStudent(), $class);
    $student = User::factory()->student()->create();

    try {
        checkIn($student, $class);
    } catch (CheckInDeniedException $exception) {
        expect($exception->getMessage())->toBe('Aula lotada');
    }

    expect($student->currentStudentPlan()->exists())->toBeFalse();
});

test('an experimental plan is offered only once per student', function () {
    $trial = Plan::factory()->trial()->create();
    $student = User::factory()->student()->create();
    StudentPlan::factory()->ended()->create([
        'user_id' => $student->id,
        'plan_id' => $trial->id,
    ]);

    checkIn($student, openClass());
})->throws(CheckInDeniedException::class, 'Você não possui um plano ativo. Procure o professor.');

test('a trial plan without capacity is not offered', function () {
    $trial = Plan::factory()->trial()->create(['max_students' => 1]);
    StudentPlan::factory()->create(['plan_id' => $trial->id]);
    $student = User::factory()->student()->create();

    checkIn($student, openClass());
})->throws(CheckInDeniedException::class, 'Você não possui um plano ativo. Procure o professor.');

test('a student with an overdue payment is blocked with the exact message', function () {
    $student = activeStudent();
    $student->payments()->save(Payment::factory()->overdue()->make());

    checkIn($student, openClass());
})->throws(
    CheckInDeniedException::class,
    'Seu plano está temporariamente bloqueado por pagamento pendente. Procure o professor para regularizar sua mensalidade.',
);

test('blocking an overdue student does not erase their history', function () {
    $student = activeStudent();
    $past = ClassSession::factory()->completed()->create();
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => $past->id,
    ]);

    $student->payments()->save(Payment::factory()->overdue()->make());

    try {
        checkIn($student, openClass());
    } catch (CheckInDeniedException) {
        // Expected: the point of this test is what survives the refusal.
    }

    expect($student->checkIns()->count())->toBe(1);
});

test('a student can not check in twice in the same class', function () {
    $student = activeStudent();
    $class = openClass();

    checkIn($student, $class);
    checkIn($student, $class);
})->throws(CheckInDeniedException::class, 'Você já fez check-in nesta aula.');

test('a student can not exceed the plan limit', function () {
    $student = activeStudent(2);

    checkIn($student, openClass());
    checkIn($student, openClass());
    checkIn($student, openClass());
})->throws(CheckInDeniedException::class, 'Você já usou todos os seus check-ins desta semana.');

test('a student can not check in to a full class', function () {
    $class = openClass(maxStudents: 1);
    checkIn(activeStudent(), $class);

    checkIn(activeStudent(), $class);
})->throws(CheckInDeniedException::class, 'Aula lotada');

test('a class never takes more students than its capacity', function () {
    $class = openClass(maxStudents: 2);

    foreach (range(1, 5) as $ignored) {
        try {
            checkIn(activeStudent(), $class);
        } catch (CheckInDeniedException) {
            // Expected once the class fills up.
        }
    }

    expect($class->fresh()->takenSpots())->toBe(2);
});

test('a student can not check in to a cancelled class', function () {
    $class = ClassSession::factory()->cancelled()->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);

    checkIn(activeStudent(), $class);
})->throws(CheckInDeniedException::class, 'Esta aula foi cancelada.');

test('a student can not check in before the window opens', function () {
    $class = ClassSession::factory()->create([
        'date' => Carbon::today()->addDays(10),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);

    checkIn(activeStudent(), $class);
})->throws(CheckInDeniedException::class, 'O check-in para esta aula ainda não está aberto.');

test('a student can not check in after the class has started', function () {
    // The frozen clock is 08:00, so this class began an hour ago.
    $class = ClassSession::factory()->create([
        'date' => Carbon::today(),
        'start_time' => '07:00',
        'end_time' => '08:30',
    ]);

    checkIn(activeStudent(), $class);
})->throws(CheckInDeniedException::class, 'O período de check-in para esta aula já encerrou.');

test('cancelling frees the seat and gives the check-in back', function () {
    $student = activeStudent(3);
    $class = openClass();

    checkIn($student, $class);
    expect(availableFor($student))->toBe(2)
        ->and($class->fresh()->takenSpots())->toBe(1);

    app(CancelCheckIn::class)->handle($student, $class);

    expect(availableFor($student))->toBe(3)
        ->and($class->fresh()->takenSpots())->toBe(0);
});

test('a cancelled seat can be taken again by the same student', function () {
    $student = activeStudent();
    $class = openClass();

    checkIn($student, $class);
    app(CancelCheckIn::class)->handle($student, $class);

    $checkIn = checkIn($student, $class);

    expect($checkIn->status)->toBe(CheckInStatus::CheckedIn)
        ->and($checkIn->cancelled_at)->toBeNull()
        ->and(CheckIn::query()->count())->toBe(1);
});

test('a student can not cancel after the deadline', function () {
    $student = activeStudent();
    $class = openClass();
    checkIn($student, $class);

    // The default deadline is two hours before the class starts.
    Carbon::setTestNow($class->startsAt()->subMinutes(30));

    app(CancelCheckIn::class)->handle($student, $class);
})->throws(CheckInDeniedException::class, 'O prazo para cancelar esta aula já encerrou.');
