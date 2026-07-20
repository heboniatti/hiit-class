<?php

use App\Actions\CheckIns\CancelCheckIn;
use App\Actions\CheckIns\PerformCheckIn;
use App\Enums\CheckInStatus;
use App\Exceptions\CheckInDeniedException;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Goal;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use App\Support\HiitSettings;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

test('the overview reports the gym at a glance', function () {
    $admin = User::factory()->admin()->create();
    $teacher = User::factory()->teacher()->create();

    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);
    User::factory()->student()->inactive()->create();

    // Reuse the teacher: the factory would otherwise invent another one and
    // throw the head count off.
    $past = ClassSession::factory()->completed()->create(['teacher_id' => $teacher->id]);
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => $past->id,
    ]);

    Payment::factory()->overdue()->create(['user_id' => $student->id]);

    $this->actingAs($admin)->get(route('management.overview'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/overview')
            // The deactivated student is not counted.
            ->where('totals.active_students', 1)
            ->where('totals.teachers', 1)
            ->where('totals.check_ins', 1)
            ->where('totals.payments_overdue', 1)
            ->where('totals.attendance_rate', 100)
            ->has('monthlyAttendance', 6),
        );
});

test('the attendance rate weighs presences against absences', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();

    foreach (range(1, 3) as $i) {
        CheckIn::factory()->present()->create([
            'user_id' => $student->id,
            'class_session_id' => ClassSession::factory()->completed(),
        ]);
    }

    CheckIn::factory()->absent()->create([
        'user_id' => $student->id,
        'class_session_id' => ClassSession::factory()->completed(),
    ]);

    $this->actingAs($admin)->get(route('management.overview'))
        ->assertInertia(fn (Assert $page) => $page->where('totals.attendance_rate', 75));
});

test('the overview shows collective goals as aggregates only', function () {
    $admin = User::factory()->admin()->create();
    Goal::factory()->collective()->create([
        'target_value' => 100,
        'starts_on' => Carbon::today()->startOfMonth(),
        'ends_on' => Carbon::today()->endOfMonth(),
    ]);

    $this->actingAs($admin)->get(route('management.overview'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('collectiveGoals', 1)
            ->where('collectiveGoals.0.target', 100)
            ->missing('collectiveGoals.0.participants'),
        );
});

test('settings are shown with their current values', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('management.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/settings')
            ->where('settings.cancellation_deadline_minutes', 120)
            ->where('settings.checkin_opens_hours_before', 48),
        );
});

test('changing the cancellation deadline changes what students can do', function () {
    $admin = User::factory()->admin()->create();
    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);

    $class = ClassSession::factory()->create([
        'date' => Carbon::today(),
        'start_time' => '19:00',
        'end_time' => '20:00',
    ]);

    app(PerformCheckIn::class)->handle($student, $class);

    // Two hours before 19:00 is 17:00, and it is 08:00: cancelling is fine.
    Carbon::setTestNow('2026-07-13 17:30:00');

    expect(fn () => app(CancelCheckIn::class)->handle($student, $class))
        ->toThrow(CheckInDeniedException::class);

    // Widen the window to 30 minutes and the very same cancellation is allowed.
    $this->actingAs($admin)->put(route('management.settings.update'), [
        HiitSettings::CHECKIN_OPENS_HOURS_BEFORE => 48,
        HiitSettings::CHECKIN_CLOSES_MINUTES_BEFORE => 0,
        HiitSettings::CANCELLATION_DEADLINE_MINUTES => 30,
        HiitSettings::SESSION_HORIZON_WEEKS => 8,
        HiitSettings::LOW_FREQUENCY_THRESHOLD => 2,
    ])->assertSessionHasNoErrors();

    $checkIn = app(CancelCheckIn::class)->handle($student, $class);

    expect($checkIn->status)->toBe(CheckInStatus::Cancelled);
});

test('settings reject nonsense', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('management.settings.edit'))
        ->put(route('management.settings.update'), [
            HiitSettings::CHECKIN_OPENS_HOURS_BEFORE => 0,
            HiitSettings::CHECKIN_CLOSES_MINUTES_BEFORE => -5,
            HiitSettings::CANCELLATION_DEADLINE_MINUTES => 'muito',
            HiitSettings::SESSION_HORIZON_WEEKS => 999,
            HiitSettings::LOW_FREQUENCY_THRESHOLD => 2,
        ])
        ->assertSessionHasErrors([
            HiitSettings::CHECKIN_OPENS_HOURS_BEFORE,
            HiitSettings::CHECKIN_CLOSES_MINUTES_BEFORE,
            HiitSettings::CANCELLATION_DEADLINE_MINUTES,
            HiitSettings::SESSION_HORIZON_WEEKS,
        ]);

    // Nothing was half-written.
    expect(app(HiitSettings::class)->cancellationDeadlineMinutes())->toBe(120);
});

test('a teacher can not reach the administrator screens', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('management.overview'))->assertForbidden();
    $this->actingAs($teacher)->get(route('management.settings.edit'))->assertForbidden();
    $this->actingAs($teacher)->put(route('management.settings.update'), [
        HiitSettings::CANCELLATION_DEADLINE_MINUTES => 5,
    ])->assertForbidden();

    // And the rule they tried to change is untouched.
    expect(app(HiitSettings::class)->cancellationDeadlineMinutes())->toBe(120);
});

test('an admin still has every teacher screen', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('management.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('management.schedule'))->assertOk();
    $this->actingAs($admin)->get(route('management.students.index'))->assertOk();
    $this->actingAs($admin)->get(route('management.plans.index'))->assertOk();
    $this->actingAs($admin)->get(route('management.payments.index'))->assertOk();
    $this->actingAs($admin)->get(route('management.goals.index'))->assertOk();
});

test('students can not reach the administrator screens', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('management.overview'))->assertForbidden();
    $this->actingAs($student)->get(route('management.settings.edit'))->assertForbidden();
});
