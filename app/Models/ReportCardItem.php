<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_card_id',
        'class_subject_id',
        'subject_snapshot',
        'teaching_history_snapshot',
        'component_breakdown_snapshot',
        'kkm_snapshot',
        'final_score',
        'kkm_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject_snapshot' => 'array',
            'teaching_history_snapshot' => 'array',
            'component_breakdown_snapshot' => 'array',
            'kkm_snapshot' => 'decimal:2',
            'final_score' => 'decimal:2',
        ];
    }

    public function reportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class);
    }

    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }
}
