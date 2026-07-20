<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property UserRole $role
 * @property UserStatus $status
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $notes
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $attended_count Classes attended; loaded by withCount() in the management screens.
 */
#[Fillable(['name', 'phone', 'email', 'password', 'role', 'status', 'notes', 'emergency_contact_name', 'emergency_contact_phone'])]
// "notes" holds internal staff remarks: HandleInertiaRequests serialises the
// whole user into every Inertia response, so it must never be visible by
// default. Management screens opt in with makeVisible('notes').
#[Hidden(['password', 'remember_token', 'notes'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Reduce a phone number to digits so that "(11) 99999-9999" and
     * "11999999999" resolve to the same account.
     *
     * A pasted "+55 11 99999-9999" is reduced to the same account too. The
     * length is what disambiguates, not the prefix: 55 is a real area code
     * (Santa Maria), so "5599999999" is left alone, while a national number
     * never runs past 11 digits — anything longer starting with 55 carries the
     * country code.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) > 11 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /**
     * Get every plan enrollment, most recent first.
     *
     * @return HasMany<StudentPlan, $this>
     */
    public function studentPlans(): HasMany
    {
        return $this->hasMany(StudentPlan::class)->latest('started_at');
    }

    /**
     * Get the student's current plan enrollment.
     *
     * @return HasOne<StudentPlan, $this>
     */
    public function currentStudentPlan(): HasOne
    {
        return $this->hasOne(StudentPlan::class)->whereNull('ended_at');
    }

    /**
     * Get the student's payments.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the student's check-ins.
     *
     * @return HasMany<CheckIn, $this>
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Get the classes this user teaches.
     *
     * @return HasMany<ClassSession, $this>
     */
    public function taughtClassSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class, 'teacher_id');
    }

    /**
     * Get the student's individual goals.
     *
     * @return HasMany<Goal, $this>
     */
    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    /**
     * Get the achievements this student has unlocked.
     *
     * @return HasMany<UserAchievement, $this>
     */
    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class);
    }

    /**
     * Determine whether the account is allowed to sign in and check in.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Determine whether the user holds the given role.
     */
    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}
