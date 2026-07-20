<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * An immutable trail entry.
 *
 * Covers both "toda alteração de pagamento deve registrar quem realizou a
 * alteração" and "toda confirmação ou cancelamento de presença deve manter um
 * histórico".
 *
 * @property int $id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property int|null $user_id
 * @property string $action
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property Carbon|null $created_at
 */
#[Fillable(['auditable_type', 'auditable_id', 'user_id', 'action', 'old_values', 'new_values'])]
class AuditLog extends Model
{
    /**
     * Entries are never updated, so only a creation timestamp is kept.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    /**
     * Get the record the entry describes.
     *
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
