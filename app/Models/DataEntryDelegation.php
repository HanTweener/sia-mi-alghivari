<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataEntryDelegation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'teaching_assignment_id',
        'capability',
        'valid_from',
        'valid_until',
        'granted_by',
        'reason',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActive(Builder $query, ?Carbon $date = null): Builder
    {
        $target = ($date ?? now())->toDateString();

        return $query->whereNull('revoked_at')
            ->where('valid_from', '<=', $target)
            ->where('valid_until', '>=', $target);
    }

    public function isActive(?Carbon $date = null): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        $target = $date ?? now();
        $targetDate = $target->toDateString();

        return $this->valid_from->toDateString() <= $targetDate
            && $this->valid_until->toDateString() >= $targetDate;
    }
}
