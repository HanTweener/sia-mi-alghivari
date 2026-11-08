<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScoringComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'source_type',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function gradingPolicyComponents(): HasMany
    {
        return $this->hasMany(GradingPolicyComponent::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function isAttendance(): bool
    {
        return $this->source_type === 'attendance';
    }
}
