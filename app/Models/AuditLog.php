<?php

namespace App\Models;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class AuditLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_user_id',
        'action',
        'entity_type',
        'entity_id',
        'scope',
        'event_visibility',
        'allowed_changes',
        'reason',
        'batch_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => AuditScope::class,
            'event_visibility' => AuditVisibility::class,
            'allowed_changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(Role::MASTER_ADMIN)) {
            return $query;
        }

        if ($user->hasRole(Role::ACADEMIC_ADMIN)) {
            return $query->whereIn('scope', [
                AuditScope::STUDENT, AuditScope::CLASSROOM, AuditScope::ASSIGNMENT,
                AuditScope::TERM, AuditScope::SUBJECT, AuditScope::ATTENDANCE,
                AuditScope::ASSESSMENT, AuditScope::REPORT, AuditScope::PROGRESSION,
            ]);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Enforce append-only integrity: prohibit updates.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new LogicException('Audit logs are append-only and cannot be modified.');
    }

    /**
     * Enforce append-only integrity: prohibit deletions.
     */
    public function delete(): ?bool
    {
        throw new LogicException('Audit logs are append-only and cannot be deleted.');
    }
}
