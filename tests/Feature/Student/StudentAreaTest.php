<?php

use App\Enums\UserStatus;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

/**
 * Build a student on an active weekly plan with no money owed.
 */
function student(int $checkInsPerWeek = 3): User
{
    $student = User::factory()->student()->create();

    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly($checkInsPerWeek),
    ]);

    return $student;
}

/**
 * Build a class this evening, inside the check-in window.
 */
function eveningClass(int $maxStudents = 20): ClassSession
{
    return ClassSession::factory()->withCapacity($maxStudents)->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);
}

test('the dashboard shows the student their next class and balance', function () {
    $student = student();
    eveningClass();

    $response = $this->actingAs($student)->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('student/dashboard')
        ->where('nextClass.can_check_in', true)
        ->where('plan.available_check_ins', 3)
        ->where('plan.payment_is_blocking', false)
        ->has('greeting')
        ->has('message'),
    );
});

test('a student checks in from the class page and the balance drops', function () {
    $student = student();
    $class = eveningClass();

    $response = $this->actingAs($student)
        ->from(route('classes.show', $class))
        ->post(route('check-ins.store', $class));

    $response->assertSessionHasNoErrors()->assertRedirect(route('classes.show', $class));

    expect($class->fresh()->takenSpots())->toBe(1);

    $this->actingAs($student)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('plan.available_check_ins', 2)
            ->where('nextClass.is_checked_in', true),
        );
});

test('a student cancels their own check-in and gets the balance back', function () {
    $student = student();
    $class = eveningClass();

    $this->actingAs($student)->post(route('check-ins.store', $class));
    $this->actingAs($student)->delete(route('check-ins.destroy', $class));

    expect($class->fresh()->takenSpots())->toBe(0);

    $this->actingAs($student)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('plan.available_check_ins', 3));
});

test('an overdue student sees the blocking reason on the class page', function () {
    $student = student();
    Payment::factory()->overdue()->create(['user_id' => $student->id]);
    $class = eveningClass();

    $this->actingAs($student)->get(route('classes.show', $class))
        ->assertInertia(fn (Assert $page) => $page
            ->where('classSession.can_check_in', false)
            ->where(
                'classSession.denial_reason',
                'Seu plano está temporariamente bloqueado por pagamento pendente. Procure o professor para regularizar sua mensalidade.',
            )
            ->where('plan.payment_is_blocking', true),
        );
});

test('a full class tells the student it is full', function () {
    $class = eveningClass(maxStudents: 1);
    $this->actingAs(student())->post(route('check-ins.store', $class));

    $this->actingAs(student())->get(route('classes.show', $class))
        ->assertInertia(fn (Assert $page) => $page
            ->where('classSession.is_full', true)
            ->where('classSession.can_check_in', false)
            ->where('classSession.denial_reason', 'Aula lotada'),
        );
});

test('the class list never exposes other students', function () {
    $student = student();
    $class = eveningClass();
    $this->actingAs(student())->post(route('check-ins.store', $class));

    $response = $this->actingAs($student)->get(route('classes.index'));

    // Only aggregate seat counts travel to the client, never who took them.
    $response->assertInertia(fn (Assert $page) => $page
        ->where('classes.0.available_spots', 19)
        ->missing('classes.0.check_ins')
        ->missing('classes.0.students'),
    );
});

test('the history lists finished classes', function () {
    $student = student();
    $past = ClassSession::factory()->completed()->create();
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => $past->id,
    ]);

    $this->actingAs($student)->get(route('history'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/history')
            ->has('history', 1)
            ->where('history.0.attended', true),
        );
});

test('the plan page is read only and shows what is owed', function () {
    $student = student();
    Payment::factory()->overdue()->create(['user_id' => $student->id]);

    $this->actingAs($student)->get(route('my-plan'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/plan')
            ->has('payments', 1)
            ->where('payments.0.is_overdue', true)
            ->where('payments.0.status', 'Vencido'),
        );
});

test('a student never sees internal notes about themselves', function () {
    $student = student();
    $student->update(['notes' => 'Lesão no joelho, evitar salto.']);

    $this->actingAs($student)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->missing('auth.user.notes'));
});

test('teachers and admins are kept out of the student area', function (string $role) {
    $staff = User::factory()->{$role}()->create();

    $this->actingAs($staff)->get(route('classes.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('history'))->assertForbidden();
    $this->actingAs($staff)->get(route('my-plan'))->assertForbidden();
})->with(['teacher', 'admin']);

test('a deactivated student is refused by the role middleware', function () {
    $student = student();
    $student->update(['status' => UserStatus::Inactive]);

    $this->actingAs($student)->get(route('classes.index'))->assertForbidden();
});

test('guests are sent to the login screen', function () {
    $this->get(route('classes.index'))->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
