<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'email_verified_at',
        'email_verified_by',
        'email_verification_source',
        'password',
        'must_change_password',
        'temporary_password_expires_at',
        'password_changed_at',
        'phone',
        'address',
        'avatar_path',
        'is_active',
        'credential_version',
        'session_generation',
        'pending_session_choice_id',
        'pending_session_choice_flow',
        'password_reset_proof_hash',
        'password_reset_proof_expires_at',
        'password_reset_proof_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_reset_proof_hash',
        'password_reset_proof_expires_at',
        'password_reset_proof_version',
        'pending_session_choice_id',
        'pending_session_choice_flow',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'nip',
    ];

    /**
     * NIP attribute fallback accessor.
     */
    public function getNipAttribute(): ?string
    {
        return null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'temporary_password_expires_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password_reset_proof_expires_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'credential_version' => 'integer',
            'session_generation' => 'integer',
        ];
    }

    /**
     * Roles relationship.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Alias for userRoles relationship.
     */
    public function roles(): HasMany
    {
        return $this->userRoles();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(Role|string $role): bool
    {
        $roleValue = $role instanceof Role ? $role->value : $role;

        return $this->userRoles->contains(fn (UserRole $ur) => $ur->role === $roleValue);
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param  array<Role|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role|string $role): UserRole
    {
        $roleValue = $role instanceof Role ? $role->value : $role;

        $assignedRole = $this->userRoles()->firstOrCreate(['role' => $roleValue]);
        $this->unsetRelation('userRoles');

        return $assignedRole;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role|string $role): void
    {
        $roleValue = $role instanceof Role ? $role->value : $role;

        $this->userRoles()->where('role', $roleValue)->delete();
        $this->unsetRelation('userRoles');
    }

    /**
     * Helper to verify if user is Master Admin.
     */
    public function isMasterAdmin(): bool
    {
        return $this->hasRole(Role::MASTER_ADMIN);
    }

    /**
     * Notifications relationship.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'recipient_id');
    }

    /**
     * Active and historical delegations granted to this user.
     */
    public function dataEntryDelegations(): HasMany
    {
        return $this->hasMany(DataEntryDelegation::class, 'user_id');
    }

    /**
     * Homeroom assignments for teacher user.
     */
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'teacher_id');
    }

    /**
     * Linked students/children for guardian user (F-07).
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_guardians', 'guardian_user_id', 'student_id')
            ->withPivot(['relationship', 'is_primary'])
            ->withTimestamps();
    }

    /**
     * Audit logs triggered by this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }
}
