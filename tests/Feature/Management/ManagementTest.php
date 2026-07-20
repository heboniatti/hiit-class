<?php

use App\Enums\CheckInStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

function teacher(): User
{
    return User::factory()->teacher()->create();
}

function studentOnPlan(int $perWeek = 3): User
{
    $student = User::factory()->student()->create();

    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly($perWeek),
    ]);

    return $student;
}

test('staff land on the management dashboard, not the student one', function (string $role) {
    $staff = User::factory()->{$role}()->create();

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertRedirect(route('management.dashboard'));
})->with(['teacher', 'admin']);

test('the dashboard surfaces the day and who needs attention', function () {
    $teacher = teacher();

    ClassSession::factory()->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
        'teacher_id' => $teacher->id,
    ]);

    // Someone who signed themselves up and cannot train yet.
    User::factory()->student()->create(['name' => 'Recém Chegado']);

    $overdue = studentOnPlan();
    Payment::factory()->overdue()->create(['user_id' => $overdue->id]);

    $this->actingAs($teacher)->get(route('management.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/dashboard')
            ->has('classesToday', 1)
            ->has('studentsWithoutPlan', 1)
            ->where('studentsWithoutPlan.0.name', 'Recém Chegado')
            ->has('studentsOverdue', 1)
            ->where('studentsOverdue.0.name', $overdue->name),
        );
});

test('a self-registered student shows up in the teacher queue and can be given a plan', function () {
    $teacher = teacher();
    $plan = Plan::factory()->weekly(2)->create();

    // The student signs themselves up.
    $this->post(route('register.store'), [
        'name' => 'Carla Aluna',
        'phone' => '11988887777',
        'password' => '123456',
        'password_confirmation' => '123456',
    ]);
    auth()->logout();

    $student = User::firstWhere('phone', '11988887777');

    $this->actingAs($teacher)->get(route('management.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('studentsWithoutPlan', 1));

    // The teacher links a plan.
    $this->actingAs($teacher)->put(route('management.students.update', $student), [
        'name' => $student->name,
        'phone' => $student->phone,
        'status' => 'active',
        'plan_id' => $plan->id,
    ])->assertSessionHasNoErrors();

    expect($student->fresh()->currentStudentPlan?->plan_id)->toBe($plan->id);

    // And the queue is empty again.
    $this->actingAs($teacher)->get(route('management.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->has('studentsWithoutPlan', 0));
});

test('marking attendance updates the roll and keeps a trail of who did it', function () {
    $teacher = teacher();
    $student = studentOnPlan();
    $class = ClassSession::factory()->create(['date' => Carbon::today()]);

    $checkIn = CheckIn::factory()->create([
        'user_id' => $student->id,
        'class_session_id' => $class->id,
    ]);

    $this->actingAs($teacher)
        ->from(route('management.classes.attendance.edit', $class))
        ->put(route('management.classes.attendance.update', $class), [
            'statuses' => [$checkIn->id => CheckInStatus::Present->value],
        ])
        ->assertSessionHasNoErrors();

    $checkIn->refresh();

    expect($checkIn->status)->toBe(CheckInStatus::Present)
        ->and($checkIn->attendance_marked_by)->toBe($teacher->id)
        ->and($checkIn->attendance_marked_at)->not->toBeNull();

    $log = AuditLog::query()->latest('id')->first();

    expect($log->action)->toBe('attendance.marked')
        ->and($log->user_id)->toBe($teacher->id)
        ->and($log->old_values)->toBe(['status' => 'checked_in'])
        ->and($log->new_values)->toBe(['status' => 'present']);
});

test('a justified absence gives the check-in back but an absence does not', function () {
    $teacher = teacher();
    $student = studentOnPlan(3);
    $class = ClassSession::factory()->create(['date' => Carbon::today()]);

    $checkIn = CheckIn::factory()->create([
        'user_id' => $student->id,
        'class_session_id' => $class->id,
    ]);

    $this->actingAs($teacher)->put(route('management.classes.attendance.update', $class), [
        'statuses' => [$checkIn->id => CheckInStatus::Absent->value],
    ]);

    expect(availableFor($student))->toBe(2);

    $this->actingAs($teacher)->put(route('management.classes.attendance.update', $class), [
        'statuses' => [$checkIn->id => CheckInStatus::JustifiedAbsence->value],
    ]);

    expect(availableFor($student))->toBe(3);
});

test('a teacher can reset a forgotten PIN and it is recorded', function () {
    $teacher = teacher();
    $student = studentOnPlan();

    $this->actingAs($teacher)
        ->from(route('management.students.show', $student))
        ->put(route('management.students.pin.update', $student), [
            'password' => '654321',
            'password_confirmation' => '654321',
        ])
        ->assertSessionHasNoErrors();

    expect(Hash::check('654321', $student->refresh()->password))->toBeTrue();

    $log = AuditLog::query()->where('action', 'student.pin_reset')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($teacher->id);

    // The student can sign in with it.
    auth()->logout();
    $this->post(route('login.store'), [
        'phone' => $student->phone,
        'password' => '654321',
    ]);
    $this->assertAuthenticatedAs($student);
});

test('a reset PIN must be six digits', function () {
    $teacher = teacher();
    $student = studentOnPlan();

    $this->actingAs($teacher)
        ->from(route('management.students.show', $student))
        ->put(route('management.students.pin.update', $student), [
            'password' => '12',
            'password_confirmation' => '12',
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check(UserFactory::PIN, $student->refresh()->password))->toBeTrue();
});

test('staff can see a student internal notes but the student never can', function () {
    $teacher = teacher();
    $student = studentOnPlan();
    $student->update(['notes' => 'Lesão no joelho.']);

    $this->actingAs($teacher)->get(route('management.students.show', $student))
        ->assertInertia(fn (Assert $page) => $page->where('student.notes', 'Lesão no joelho.'));

    $this->actingAs($student)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->missing('auth.user.notes'));
});

test('deactivating a student keeps their history', function () {
    $teacher = teacher();
    $student = studentOnPlan();
    $past = ClassSession::factory()->completed()->create();
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => $past->id,
    ]);

    $this->actingAs($teacher)->put(route('management.students.update', $student), [
        'name' => $student->name,
        'phone' => $student->phone,
        'status' => 'inactive',
    ])->assertSessionHasNoErrors();

    expect($student->fresh()->isActive())->toBeFalse()
        ->and($student->checkIns()->count())->toBe(1);
});

test('a staff account can not be opened through the student screens', function () {
    $teacher = teacher();
    $otherTeacher = teacher();

    $this->actingAs($teacher)
        ->get(route('management.students.show', $otherTeacher))
        ->assertNotFound();
});

test('a student created by staff is never given a staff role', function () {
    $teacher = teacher();

    $this->actingAs($teacher)->post(route('management.students.store'), [
        'name' => 'Novo Aluno',
        'phone' => '11977776666',
        'status' => 'active',
        'password' => '123456',
        'password_confirmation' => '123456',
        // Smuggled in; must be ignored.
        'role' => 'admin',
    ])->assertSessionHasNoErrors();

    expect(User::firstWhere('phone', '11977776666')->role)->toBe(UserRole::Student);
});

test('students are kept out of the management area', function () {
    $student = studentOnPlan();
    $class = ClassSession::factory()->create();

    $this->actingAs($student)->get(route('management.dashboard'))->assertForbidden();
    $this->actingAs($student)->get(route('management.students.index'))->assertForbidden();
    $this->actingAs($student)->get(route('management.classes.attendance.edit', $class))->assertForbidden();
    $this->actingAs($student)->put(route('management.students.pin.update', $student), [
        'password' => '654321',
        'password_confirmation' => '654321',
    ])->assertForbidden();
});
