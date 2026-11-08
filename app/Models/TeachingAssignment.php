<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_subject_id',
        'teacher_id',
        'starts_on',
        'ends_on',
        'status',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function dataEntryDelegations(): HasMany
    {
        return $this->hasMany(DataEntryDelegation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'assigned')
            ->whereDate('starts_on', '<=', today())
            ->whereNull('ends_on');
    }

    public function isEffectiveOn(?Carbon $date = null): bool
    {
        $target = $date ?? now();

        if ($this->status !== 'assigned') {
            return false;
        }

        if ($this->starts_on->isAfter($target)) {
            return false;
        }

        if ($this->ends_on && $this->ends_on->isBefore($target)) {
            return false;
        }

        return true;
    }
}
