<?php

namespace App\Http\Controllers\Management;

use App\Concerns\PinValidationRules;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentPinController extends Controller
{
    use PinValidationRules;

    /**
     * Give a student a new PIN.
     *
     * This is the only way back in for a student who forgot theirs: they have
     * no email, so there is no self-service reset. The new PIN is recorded in
     * the trail, but never the PIN itself.
     */
    public function update(Request $request, User $student): RedirectResponse
    {
        abort_unless($student->role === UserRole::Student, 404);

        $validated = $request->validate(
            ['password' => $this->pinRules()],
            [
                'password.required' => 'Informe o novo PIN.',
                'password.digits' => 'O PIN deve ter exatamente 6 números.',
                'password.confirmed' => 'A confirmação do PIN não confere.',
            ],
        );

        $student->update(['password' => $validated['password']]);

        AuditLog::create([
            'auditable_type' => $student->getMorphClass(),
            'auditable_id' => $student->id,
            'user_id' => $request->user()->id,
            'action' => 'student.pin_reset',
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('PIN redefinido. Informe o novo PIN ao aluno.'),
        ]);

        return back();
    }
}
