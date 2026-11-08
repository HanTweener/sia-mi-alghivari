<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradingPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'revision',
        'kkm',
        'status',
        'created_by',
        'activated_by',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'kkm' => 'decimal:2',
            'activated_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function components(): HasMany
    {
        return $this->hasMany(GradingPolicyComponent::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isRetired(): bool
    {
        return $this->status === 'retired';
    }

    public function totalWeight(): float
    {
        return (float) $this->components->sum('weight_percentage');
    }
}
