<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class P5Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_card_id',
        'project_title',
        'dimension',
        'predicate',
        'description',
    ];

    public const PREDICATES = ['BB', 'MB', 'BSH', 'SAB'];

    public const PREDICATE_LABELS = [
        'BB' => 'Belum Berkembang',
        'MB' => 'Mulai Berkembang',
        'BSH' => 'Berkembang Sesuai Harapan',
        'SAB' => 'Sangat Berkembang',
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
