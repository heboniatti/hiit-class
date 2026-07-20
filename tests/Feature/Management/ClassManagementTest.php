<?php

use App\Actions\Classes\GenerateClassSessions;
use App\Enums\CheckInStatus;
use App\Enums\ClassSessionStatus;
use App\Models\CheckIn;
use App\Models\ClassSchedule;
use App\Models\ClassSession;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

// A Monday, so the weekday maths is easy to follow.
beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

function staff(): User
{
    return User::factory()->teacher()->create();
}

test('the schedule shows one week at a time', function () {
    $teacher = staff();

    ClassSession::factory()->create([
        'date' => Carbon::today(),
        'teacher_id' => $teacher->id,
    ]);
    // Next week: must not appear in the current week.
    ClassSession::factory()->create([
        'date' => Carbon::today()->addWeek(),
        'teacher_id' => $teacher->id,
    ]);

    $this->actingAs($teacher)->get(route('management.schedule'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/schedule')
            ->has('classes', 1)
            ->where('week.is_current', true),
        );

    $this->actingAs($teacher)->get(route('management.schedule', ['week' => 1]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('classes', 1)
            ->where('week.is_current', false),
        );
});

test('a teacher creates a one-off class', function () {
    $teacher = staff();

    $this->actingAs($teacher)->post(route('management.classes.store'), [
        'name' => 'HIIT Especial',
        'teacher_id' => $teacher->id,
        'is_recurring' => false,
        'date' => Carbon::today()->addDays(2)->toDateString(),
        'start_time' => '19:00',
        'end_time' => '20:00',
        'max_students' => 15,
    ])->assertSessionHasNoErrors()->assertRedirect(route('management.schedule'));

    $class = ClassSession::firstWhere('name', 'HIIT Especial');

    expect($class)->not->toBeNull()
        ->and($class->max_students)->toBe(15)
        ->and($class->class_schedule_id)->toBeNull()
        ->and($class->status)->toBe(ClassSessionStatus::Scheduled);
});

test('a recurring class materialises every matching weekday in the horizon', function () {
    $teacher = staff();

    $this->actingAs($teacher)->post(route('management.classes.store'), [
        'name' => 'HIIT Noite',
        'teacher_id' => $teacher->id,
        'is_recurring' => true,
        // Monday, Wednesday, Friday.
        'days_of_week' => [1, 3, 5],
        'starts_on' => Carbon::today()->toDateString(),
        'start_time' => '19:00',
        'end_time' => '20:00',
        'max_students' => 20,
    ])->assertSessionHasNoErrors();

    $schedule = ClassSchedule::firstWhere('name', 'HIIT Noite');
    $classes = ClassSession::where('class_schedule_id', $schedule->id)->get();

    // Default horizon is 8 weeks: 3 classes a week, starting today.
    expect($classes)->toHaveCount(25)
        ->and($classes->every(fn (ClassSession $c): bool => in_array(
            $c->date->dayOfWeekIso, [1, 3, 5], true,
        )))->toBeTrue()
        ->and($classes->every(fn (ClassSession $c): bool => $c->max_students === 20))->toBeTrue();
});

test('generating a recurrence twice creates nothing new', function () {
    $schedule = ClassSchedule::factory()->create(['starts_on' => Carbon::today()]);
    $generate = app(GenerateClassSessions::class);

    $first = $generate->handle($schedule);
    $second = $generate->handle($schedule);

    expect($first)->toBeGreaterThan(0)
        ->and($second)->toBe(0)
        ->and(ClassSession::where('class_schedule_id', $schedule->id)->count())->toBe($first);
});

test('the generate command is safe to run again', function () {
    ClassSchedule::factory()->create(['starts_on' => Carbon::today()]);

    $this->artisan('classes:generate')->assertSuccessful();
    $created = ClassSession::count();

    $this->artisan('classes:generate')->assertSuccessful();

    expect(ClassSession::count())->toBe($created);
});

test('a recurrence never invents classes in the past', function () {
    $schedule = ClassSchedule::factory()->create([
        'starts_on' => Carbon::today()->subMonths(2),
    ]);

    app(GenerateClassSessions::class)->handle($schedule);

    $earliest = ClassSession::where('class_schedule_id', $schedule->id)->min('date');

    expect(Carbon::parse($earliest)->gte(Carbon::today()))->toBeTrue();
});

test('a recurrence stops at its end date', function () {
    $schedule = ClassSchedule::factory()->create([
        'days_of_week' => [1, 3, 5],
        'starts_on' => Carbon::today(),
        'ends_on' => Carbon::today()->addDays(7),
    ]);

    app(GenerateClassSessions::class)->handle($schedule);

    $latest = ClassSession::where('class_schedule_id', $schedule->id)->max('date');

    expect(Carbon::parse($latest)->lte(Carbon::today()->addDays(7)))->toBeTrue();
});

test('editing one class of a series leaves the others alone', function () {
    $teacher = staff();
    $schedule = ClassSchedule::factory()->create(['starts_on' => Carbon::today()]);
    app(GenerateClassSessions::class)->handle($schedule);

    $class = ClassSession::where('class_schedule_id', $schedule->id)->orderBy('date')->first();
    $siblings = ClassSession::where('class_schedule_id', $schedule->id)
        ->whereNot('id', $class->id)
        ->count();

    $this->actingAs($teacher)
        ->from(route('management.classes.edit', $class))
        ->put(route('management.classes.update', $class), [
            'name' => 'HIIT Remarcado',
            'teacher_id' => $teacher->id,
            'is_recurring' => false,
            'date' => $class->date->toDateString(),
            'start_time' => '20:00',
            'end_time' => '21:00',
            'max_students' => 10,
        ])->assertSessionHasNoErrors();

    expect($class->fresh()->name)->toBe('HIIT Remarcado')
        ->and(ClassSession::where('name', 'HIIT Remarcado')->count())->toBe(1)
        ->and(ClassSession::where('class_schedule_id', $schedule->id)->count())
        ->toBe($siblings + 1);
});

test('cancelling a class keeps it and gives the students their check-ins back', function () {
    $teacher = staff();
    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);

    $class = ClassSession::factory()->create(['date' => Carbon::today()]);
    CheckIn::factory()->create([
        'user_id' => $student->id,
        'class_session_id' => $class->id,
    ]);

    expect(availableFor($student))->toBe(2);

    $this->actingAs($teacher)
        ->delete(route('management.classes.destroy', $class))
        ->assertSessionHasNoErrors();

    // The class survives for the reports; only its status changes.
    expect(ClassSession::find($class->id))->not->toBeNull()
        ->and($class->fresh()->status)->toBe(ClassSessionStatus::Cancelled)
        // The student's row survives too, so their history is intact.
        ->and(CheckIn::where('class_session_id', $class->id)->count())->toBe(1)
        ->and(CheckIn::where('class_session_id', $class->id)->first()->status)
        ->toBe(CheckInStatus::Cancelled)
        ->and(availableFor($student))->toBe(3);
});

test('the end time must be after the start time', function () {
    $teacher = staff();

    $this->actingAs($teacher)
        ->from(route('management.classes.create'))
        ->post(route('management.classes.store'), [
            'name' => 'HIIT',
            'teacher_id' => $teacher->id,
            'is_recurring' => false,
            'date' => Carbon::today()->toDateString(),
            'start_time' => '20:00',
            'end_time' => '19:00',
            'max_students' => 10,
        ])->assertSessionHasErrors('end_time');
});

test('a recurrence needs at least one weekday', function () {
    $teacher = staff();

    $this->actingAs($teacher)
        ->from(route('management.classes.create'))
        ->post(route('management.classes.store'), [
            'name' => 'HIIT',
            'teacher_id' => $teacher->id,
            'is_recurring' => true,
            'starts_on' => Carbon::today()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'max_students' => 10,
        ])->assertSessionHasErrors('days_of_week');
});

test('a student can not be made the teacher of a class', function () {
    $teacher = staff();
    $student = User::factory()->student()->create();

    $this->actingAs($teacher)
        ->from(route('management.classes.create'))
        ->post(route('management.classes.store'), [
            'name' => 'HIIT',
            'teacher_id' => $student->id,
            'is_recurring' => false,
            'date' => Carbon::today()->toDateString(),
            'start_time' => '19:00',
            'end_time' => '20:00',
            'max_students' => 10,
        ])->assertSessionHasErrors('teacher_id');
});

test('students can not reach the class management screens', function () {
    $student = User::factory()->student()->create();
    $class = ClassSession::factory()->create();

    $this->actingAs($student)->get(route('management.schedule'))->assertForbidden();
    $this->actingAs($student)->get(route('management.classes.create'))->assertForbidden();
    $this->actingAs($student)->delete(route('management.classes.destroy', $class))->assertForbidden();
});
