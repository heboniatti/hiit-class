<?php

use App\Models\CheckIn;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\StudentPlan;
use App\Models\User;
use App\Reports\ReportRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Carbon::setTestNow('2026-07-13 08:00:00'));

function boss(): User
{
    return User::factory()->admin()->create();
}

/**
 * Read a streamed download into a string.
 */
function download(TestResponse $response): string
{
    ob_start();
    $response->sendContent();

    return (string) ob_get_clean();
}

test('the reports screen opens on a report with its rows', function () {
    $admin = boss();
    $student = User::factory()->student()->create(['name' => 'Carla Aluna']);
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);

    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => ClassSession::factory()->create(['date' => Carbon::today()]),
    ]);

    $this->actingAs($admin)->get(route('management.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('management/reports/index')
            ->has('reports', 12)
            ->where('current.key', 'student-attendance')
            ->has('headings')
            ->where('rows.0.0', 'Carla Aluna')
            ->where('rows.0.4', 1)
            ->where('rows.0.6', '100%'),
        );
});

test('every report renders and exports without blowing up', function (string $key) {
    $admin = boss();
    $student = User::factory()->student()->create();
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => ClassSession::factory()->create(['date' => Carbon::today()]),
    ]);
    Payment::factory()->paid()->create(['user_id' => $student->id]);

    $this->actingAs($admin)
        ->get(route('management.reports.index', ['report' => $key]))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('management.reports.export', ['report' => $key]))
        ->assertOk();
})->with([
    'student-attendance',
    'check-ins',
    'attendance-rate',
    'absences',
    'class-occupancy',
    'popular-times',
    'teacher-activity',
    'low-frequency',
    'pending-payments',
    'received-payments',
    'plan-usage',
    'goal-progress',
]);

test('the registry offers exactly the reports the screen lists', function () {
    $keys = array_map(fn ($report) => $report->key(), app(ReportRegistry::class)->all());

    // Guards the dataset above from silently drifting out of date.
    expect($keys)->toHaveCount(12)
        ->and(array_unique($keys))->toHaveCount(12);
});

test('the export opens correctly in a Brazilian spreadsheet', function () {
    $admin = boss();
    $student = User::factory()->student()->create(['name' => 'João Conceição']);
    StudentPlan::factory()->create([
        'user_id' => $student->id,
        'plan_id' => Plan::factory()->weekly(3),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('management.reports.export', ['report' => 'student-attendance']));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload('student-attendance-2026-06-13-a-2026-07-13.csv');

    $csv = download($response);

    // The byte order mark, without which Excel mangles every accent.
    expect($csv)->toStartWith("\xEF\xBB\xBF")
        // Semicolons, because a pt-BR Excel drops a comma file into column A.
        ->and($csv)->toContain('Aluno;Telefone;Plano')
        // And the accents survive the round trip.
        ->and($csv)->toContain('João Conceição');
});

test('the export respects the filters', function () {
    $admin = boss();
    $wanted = User::factory()->student()->create(['name' => 'Aluno Filtrado']);
    $other = User::factory()->student()->create(['name' => 'Aluno de Fora']);

    foreach ([$wanted, $other] as $student) {
        StudentPlan::factory()->create([
            'user_id' => $student->id,
            'plan_id' => Plan::factory()->weekly(3),
        ]);
    }

    $csv = download($this->actingAs($admin)->get(route('management.reports.export', [
        'report' => 'student-attendance',
        'student_id' => $wanted->id,
    ])));

    expect($csv)->toContain('Aluno Filtrado')
        ->and($csv)->not->toContain('Aluno de Fora');
});

test('the period filter excludes what falls outside it', function () {
    $admin = boss();
    $student = User::factory()->student()->create();

    // Inside the window.
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => ClassSession::factory()->create(['date' => '2026-07-10']),
    ]);
    // Long before it.
    CheckIn::factory()->present()->create([
        'user_id' => $student->id,
        'class_session_id' => ClassSession::factory()->create(['date' => '2026-01-10']),
    ]);

    $this->actingAs($admin)->get(route('management.reports.index', [
        'report' => 'check-ins',
        'from' => '2026-07-01',
        'to' => '2026-07-31',
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('rows', 1)
        ->where('rows.0.0', '10/07/2026'),
    );
});

test('the screen says out loud when it is not showing everything', function () {
    $admin = boss();
    $student = User::factory()->student()->create();

    // 201 rows: one past the preview cap.
    foreach (range(1, 201) as $i) {
        CheckIn::factory()->present()->create([
            'user_id' => $student->id,
            'class_session_id' => ClassSession::factory()->create(['date' => Carbon::today()]),
        ]);
    }

    $this->actingAs($admin)->get(route('management.reports.index', ['report' => 'check-ins']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('total', 201)
            ->has('rows', 200)
            // Never let a capped list imply it covered the lot.
            ->where('truncated', true),
        );
});

test('an unknown report is a 404, not a blank screen', function () {
    $admin = boss();

    $this->actingAs($admin)
        ->get(route('management.reports.index', ['report' => 'inventado']))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('management.reports.export', ['report' => 'inventado']))
        ->assertNotFound();
});

test('reports are for administrators alone', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();

    foreach ([$teacher, $student] as $user) {
        $this->actingAs($user)->get(route('management.reports.index'))->assertForbidden();
        $this->actingAs($user)
            ->get(route('management.reports.export', ['report' => 'student-attendance']))
            ->assertForbidden();
    }
});

test('the low frequency report is a worklist, not a ranking', function () {
    $admin = boss();

    $quiet = User::factory()->student()->create(['name' => 'Aluno Sumido']);
    $regular = User::factory()->student()->create(['name' => 'Aluno Assíduo']);

    foreach ([$quiet, $regular] as $student) {
        StudentPlan::factory()->create([
            'user_id' => $student->id,
            'plan_id' => Plan::factory()->weekly(3),
        ]);
    }

    foreach (range(1, 5) as $i) {
        CheckIn::factory()->present()->create([
            'user_id' => $regular->id,
            'class_session_id' => ClassSession::factory()->create(['date' => Carbon::today()->subDays($i)]),
        ]);
    }

    $this->actingAs($admin)->get(route('management.reports.index', ['report' => 'low-frequency']))
        ->assertInertia(fn (Assert $page) => $page
            // Only the student who needs a nudge; the regular one is not listed
            // for comparison.
            ->has('rows', 1)
            ->where('rows.0.0', 'Aluno Sumido'),
        );
});
