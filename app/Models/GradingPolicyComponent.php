<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradingPolicyComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'grading_policy_id',
        'scoring_component_id',
        'weight_percentage',
    ];

    protected function casts(): array
    {
        return [
            'weight_percentage' => 'decimal:2',
        ];
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(GradingPolicy::class, 'grading_policy_id');
    }

    public function scoringComponent(): BelongsTo
    {
        return $this->belongsTo(ScoringComponent::class, 'scoring_component_id');
    }
}
