<?php

use App\Actions\Gamification\CalculateStreak;
use App\Actions\Gamification\EvaluateAchievements;
use App\Enums\CheckInStatus;
use App\Enums\GoalType;
use App\Models\Achievement;
use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Goal;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use App\Models\UserAchievement;
use Database\Seeders\AchievementSeeder;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

// A Monday.
beforeEach(function () {
    Carbon::setTestNow('2026-07-13 08:00:00');
    $this->seed(AchievementSeeder::class);
});

function learner(): User
{
    $student = User::factory()->student()->create();

    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);

    return $student;
}

/**
 * Get the codes of every achievement the student has unlocked.
 *
 * @return array<int, string>
 */
function achievementCodesFor(User $student): array
{
    return UserAchievement::query()
        ->where('user_id', $student->id)
        ->with('achievement')
        ->get()
        ->pluck('achievement.code')
        ->all();
}

/**
 * Record the student as present at a class on the given date.
 */
function attended(User $student, Carbon $date): CheckIn
{
    $class = ClassSession::factory()->create(['date' => $date]);

    return CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => $class->id,
    ]);
}

test('a streak counts consecutive weeks of training', function () {
    $student = learner();

    attended($student, Carbon::today());
    attended($student, Carbon::today()->subWeek());
    attended($student, Carbon::today()->subWeeks(2));

    expect(app(CalculateStreak::class)->handle($student))->toBe(3);
});

test('a gap breaks the streak', function () {
    $student = learner();

    attended($student, Carbon::today());
    // Nothing last week.
    attended($student, Carbon::today()->subWeeks(2));

    expect(app(CalculateStreak::class)->handle($student))->toBe(1);
});

test('the current week does not break a streak while it is still running', function () {
    $student = learner();

    // Trained the last three weeks, nothing yet this week. It is Monday
    // morning: the streak must not be reported as dead.
    attended($student, Carbon::today()->subWeek());
    attended($student, Carbon::today()->subWeeks(2));
    attended($student, Carbon::today()->subWeeks(3));

    expect(app(CalculateStreak::class)->handle($student))->toBe(3);
});

test('a student who never trained has no streak', function () {
    expect(app(CalculateStreak::class)->handle(learner()))->toBe(0);
});

test('goal progress is derived from attendance', function () {
    $student = learner();
    $goal = Goal::factory()->create([
        'user_id' => $student->id,
        'type' => GoalType::ClassesInPeriod,
        'target_value' => 4,
        'starts_on' => Carbon::today()->startOfMonth(),
        'ends_on' => Carbon::today()->endOfMonth(),
    ]);

    attended($student, Carbon::today());
    attended($student, Carbon::today()->subDay());

    $this->actingAs($student)->get(route('goals'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/goals')
            ->where('personalGoals.0.current', 2)
            ->where('personalGoals.0.target', 4)
            ->where('personalGoals.0.percent', 50)
            ->where('personalGoals.0.is_complete', false),
        );
});

test('a collective goal sums the whole group', function () {
    $student = learner();
    $other = learner();

    Goal::factory()->collective()->create([
        'type' => GoalType::ClassesInPeriod,
        'target_value' => 10,
        'starts_on' => Carbon::today()->startOfMonth(),
        'ends_on' => Carbon::today()->endOfMonth(),
    ]);

    attended($student, Carbon::today());
    attended($other, Carbon::today());
    attended($other, Carbon::today()->subDay());

    $this->actingAs($student)->get(route('goals'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('groupGoals.0.current', 3)
            ->where('groupGoals.0.target', 10)
            ->where('groupGoals.0.percent', 30),
        );
});

test('a collective goal never exposes who contributed', function () {
    $student = learner();
    $other = User::factory()->student()->create(['name' => 'Fulano Silva']);
    StudentPlan::factory()->create([
        'user_id' => $other->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);

    Goal::factory()->collective()->create([
        'target_value' => 10,
        'starts_on' => Carbon::today()->startOfMonth(),
        'ends_on' => Carbon::today()->endOfMonth(),
    ]);

    attended($other, Carbon::today());

    $response = $this->actingAs($student)->get(route('goals'));

    // The aggregate travels; the people behind it never do.
    $response->assertInertia(fn (Assert $page) => $page
        ->where('groupGoals.0.current', 1)
        ->missing('groupGoals.0.participants')
        ->missing('groupGoals.0.students')
        ->missing('groupGoals.0.ranking'),
    );

    // No other student's name reaches the page at all.
    expect($response->getContent())->not->toContain('Fulano Silva');
});

test('the group goal is encouraged, never ranked', function () {
    $student = learner();
    Goal::factory()->collective()->create([
        'target_value' => 100,
        'starts_on' => Carbon::today()->startOfMonth(),
        'ends_on' => Carbon::today()->endOfMonth(),
    ]);

    $response = $this->actingAs($student)->get(route('goals'));

    $response->assertInertia(fn (Assert $page) => $page->has('groupGoals.0.encouragement'));

    // The vocabulary the spec rules out must not appear anywhere.
    foreach (['melhor aluno', 'pior desempenho', 'último colocado', 'vencedor', 'perdedor', 'ranking'] as $banned) {
        expect(mb_strtolower($response->getContent()))->not->toContain($banned);
    }
});

test('confirming attendance unlocks the first class achievement', function () {
    $teacher = User::factory()->teacher()->create();
    $student = learner();
    $class = ClassSession::factory()->create(['date' => Carbon::today()]);
    $checkIn = CheckIn::factory()->create([
        'user_id' => $student->id,
        'class_session_id' => $class->id,
    ]);

    expect(UserAchievement::where('user_id', $student->id)->count())->toBe(0);

    $this->actingAs($teacher)->put(route('management.classes.attendance.update', $class), [
        'statuses' => [$checkIn->id => CheckInStatus::Present->value],
    ])->assertSessionHasNoErrors();

    $unlocked = UserAchievement::query()
        ->where('user_id', $student->id)
        ->with('achievement')
        ->get()
        ->pluck('achievement.code');

    expect($unlocked)->toContain('first_class');
});

test('an achievement is never unlocked twice', function () {
    $student = learner();

    foreach (range(1, 3) as $i) {
        attended($student, Carbon::today()->subDays($i));
        app(EvaluateAchievements::class)->handle($student);
    }

    $first = Achievement::firstWhere('code', 'first_class');

    expect(UserAchievement::where('user_id', $student->id)->where('achievement_id', $first->id)->count())
        ->toBe(1);
});

test('five and ten class achievements unlock at their thresholds', function () {
    $student = learner();
    $evaluate = app(EvaluateAchievements::class);

    foreach (range(1, 4) as $i) {
        attended($student, Carbon::today()->subDays($i));
    }
    $evaluate->handle($student);

    expect(achievementCodesFor($student))->toContain('first_class')
        ->and(achievementCodesFor($student))->not->toContain('five_classes');

    attended($student, Carbon::today()->subDays(5));
    $evaluate->handle($student);

    expect(achievementCodesFor($student))->toContain('five_classes')
        ->and(achievementCodesFor($student))->not->toContain('ten_classes');
});

test('the goals page shows the catalogue with locked and unlocked achievements', function () {
    $student = learner();

    $this->actingAs($student)->get(route('goals'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('achievements', 7)
            ->where('achievements.0.is_unlocked', false)
            ->has('streak'),
        );
});

test('achievements are listed with the nearest first and the open-ended ones last', function () {
    $student = learner();

    $this->actingAs($student)->get(route('goals'))
        ->assertInertia(fn (Assert $page) => $page
            // The first thing a new student can earn leads the list.
            ->where('achievements.0.name', 'Primeira aula concluída')
            // The three without a threshold (comeback, monthly goal, group
            // goal) trail, rather than heading the list as SQLite's NULL
            // ordering would otherwise put them.
            ->where('achievements.4.description', 'Você voltou a treinar. Bom te ver de novo!')
            ->where('achievements.6.name', 'Participação em meta coletiva'),
        );
});

test('staff can create an individual goal and a group goal', function () {
    $teacher = User::factory()->teacher()->create();
    $student = learner();

    $this->actingAs($teacher)->post(route('management.goals.store'), [
        'title' => 'Participar de 8 aulas no mês',
        'type' => GoalType::ClassesInPeriod->value,
        'target_value' => 8,
        'starts_on' => Carbon::today()->toDateString(),
        'ends_on' => Carbon::today()->endOfMonth()->toDateString(),
        'is_collective' => false,
        'user_id' => $student->id,
    ])->assertSessionHasNoErrors();

    $this->actingAs($teacher)->post(route('management.goals.store'), [
        'title' => 'O grupo completar 100 aulas',
        'type' => GoalType::ClassesInPeriod->value,
        'target_value' => 100,
        'starts_on' => Carbon::today()->toDateString(),
        'ends_on' => Carbon::today()->endOfMonth()->toDateString(),
        'is_collective' => true,
    ])->assertSessionHasNoErrors();

    expect(Goal::individual()->count())->toBe(1)
        ->and(Goal::collective()->count())->toBe(1)
        ->and(Goal::collective()->first()->created_by)->toBe($teacher->id);
});

test('an individual goal must name a student', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->from(route('management.goals.index'))
        ->post(route('management.goals.store'), [
            'title' => 'Meta sem dono',
            'type' => GoalType::ClassesInPeriod->value,
            'target_value' => 8,
            'starts_on' => Carbon::today()->toDateString(),
            'ends_on' => Carbon::today()->endOfMonth()->toDateString(),
            'is_collective' => false,
        ])->assertSessionHasErrors('user_id');
});

test('students can not reach the goal management screens', function () {
    $student = learner();

    $this->actingAs($student)->get(route('management.goals.index'))->assertForbidden();
    $this->actingAs($student)->post(route('management.goals.store'), [])->assertForbidden();
});

test('staff are kept out of the student goals screen', function () {
    $this->actingAs(User::factory()->teacher()->create())
        ->get(route('goals'))
        ->assertForbidden();
});
