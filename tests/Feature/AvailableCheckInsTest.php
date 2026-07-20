<?php

use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

/**
 * Enroll a student on the given plan.
 */
function enroll(Plan $plan, ?Carbon $startedAt = null): User
{
    $student = User::factory()->student()->create();

    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => $plan->id,
        'started_at' => $startedAt ?? Carbon::today()->subMonths(2),
    ]);

    return $student;
}

/**
 * Give the student a check-in on a class held on the given date.
 */
function checkInOn(User $student, Carbon $date, string $state = 'present'): CheckIn
{
    $class = ClassSession::factory()->create(['date' => $date]);

    return CheckIn::factory()->{$state}()->create([
        'user_id' => $student->id,
        'class_session_id' => $class->id,
    ]);
}

test('a weekly plan starts the week with its full quota', function () {
    $student = enroll(Plan::factory()->weekly(3)->create());

    expect(availableFor($student))->toBe(3);
});

test('an unlimited plan reports no limit', function () {
    $student = enroll(Plan::factory()->unlimited()->create());

    expect(availableFor($student))->toBeNull();
});

test('the quota renews on its own when the week rolls over', function () {
    $student = enroll(Plan::factory()->weekly(2)->create());

    // Spend both check-ins on last week's classes.
    checkInOn($student, Carbon::today()->subWeek());
    checkInOn($student, Carbon::today()->subWeek()->addDay());

    expect(availableFor($student, Carbon::today()->subWeek()))->toBe(0)
        ->and(availableFor($student))->toBe(2);
});

test('the quota is spent by the class date, not by when the student booked', function () {
    $student = enroll(Plan::factory()->weekly(2)->create());

    // Booked today (Monday) for a class next week.
    checkInOn($student, Carbon::today()->addWeek(), 'checkedIn');

    expect(availableFor($student))->toBe(2)
        ->and(availableFor($student, Carbon::today()->addWeek()))->toBe(1);
});

test('unused check-ins do not carry over by default', function () {
    $student = enroll(Plan::factory()->weekly(3)->create());

    // Nothing used last week.
    expect(availableFor($student))->toBe(3);
});

test('unused check-ins carry over when the plan allows it', function () {
    $student = enroll(Plan::factory()->weekly(3)->withRollover()->create());

    // Used one of last week's three, so two are left over.
    checkInOn($student, Carbon::today()->subWeek());

    expect(availableFor($student))->toBe(5);
});

test('rollover is capped at a single week of quota', function () {
    $student = enroll(
        Plan::factory()->weekly(3)->withRollover()->create(),
        Carbon::today()->subMonths(2),
    );

    // Two whole weeks untouched would be six banked check-ins without a cap.
    expect(availableFor($student))->toBe(6);
});

test('rollover never reaches back before the student joined', function () {
    $student = enroll(
        Plan::factory()->weekly(3)->withRollover()->create(),
        Carbon::today(),
    );

    expect(availableFor($student))->toBe(3);
});

test('a trial plan grants exactly one check-in', function () {
    $student = enroll(Plan::factory()->trial()->create());

    expect(availableFor($student))->toBe(1);
});

test('a used trial check-in is not renewed the following week', function () {
    $student = enroll(Plan::factory()->trial()->create(), Carbon::today()->subWeek());

    checkInOn($student, Carbon::today()->subWeek());

    expect(availableFor($student))->toBe(0)
        ->and(availableFor($student, Carbon::today()->addWeek()))->toBe(0);
});

test('a cancelled check-in gives the quota back', function () {
    $student = enroll(Plan::factory()->weekly(2)->create());

    checkInOn($student, Carbon::today(), 'cancelled');

    expect(availableFor($student))->toBe(2);
});

test('an absence still spends the check-in', function () {
    $student = enroll(Plan::factory()->weekly(2)->create());

    checkInOn($student, Carbon::today(), 'absent');

    expect(availableFor($student))->toBe(1);
});

test('a justified absence does not spend the check-in', function () {
    $student = enroll(Plan::factory()->weekly(2)->create());

    checkInOn($student, Carbon::today(), 'justified');

    expect(availableFor($student))->toBe(2);
});
