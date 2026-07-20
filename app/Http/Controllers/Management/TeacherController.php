<?php

namespace App\Http\Controllers\Management;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Management\TeacherRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Show the teacher registration form and the existing teaching team.
     */
    public function index(): Response
    {
        $teachers = User::query()
            ->where('role', UserRole::Teacher)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email', 'status', 'created_at'])
            ->map(fn (User $teacher): array => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'phone' => $teacher->phone,
                'email' => $teacher->email,
                'status' => $teacher->status->label(),
                'is_active' => $teacher->status === UserStatus::Active,
                'registered_at' => $teacher->created_at?->format('d/m/Y') ?? '',
            ])
            ->values();

        return Inertia::render('management/teachers/index', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Create an active teacher account with a phone and initial PIN.
     */
    public function store(TeacherRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->validated('name'),
            'phone' => $request->validated('phone'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => UserRole::Teacher,
            'status' => UserStatus::Active,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Professor cadastrado.'),
        ]);

        return to_route('management.teachers.index');
    }
}
