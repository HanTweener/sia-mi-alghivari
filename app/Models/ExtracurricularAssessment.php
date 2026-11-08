<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_card_id',
        'activity_name',
        'predicate',
        'description',
    ];

    public const PREDICATES = ['A', 'B', 'C', 'D'];

    public const PREDICATE_LABELS = [
        'A' => 'Sangat Baik',
        'B' => 'Baik',
        'C' => 'Cukup',
        'D' => 'Kurang',
    ];

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function getPredicateLabelAttribute(): string
    {
        return self::PREDICATE_LABELS[$this->predicate] ?? $this->predicate;
    }
}
