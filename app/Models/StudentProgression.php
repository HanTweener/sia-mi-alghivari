<?php

namespace App\Models;

use App\Enums\ProgressionStatus;
use App\Enums\ProgressionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgression extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'source_academic_year_id',
        'source_report_card_id',
        'type',
        'target_academic_year_id',
        'target_classroom_id',
        'status',
        'version',
        'proposed_by',
        'approved_by',
        'approved_at',
        'applied_by',
        'applied_at',
        'reason',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProgressionType::class,
            'status' => ProgressionStatus::class,
            'version' => 'integer',
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sourceAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'source_academic_year_id');
    }

    public function sourceReportCard(): BelongsTo
    {
        return $this->belongsTo(ReportCard::class, 'source_report_card_id');
    }

    public function targetAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'target_academic_year_id');
    }

    public function targetClassroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'target_classroom_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function isDraft(): bool
    {
        return $this->status === ProgressionStatus::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === ProgressionStatus::SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === ProgressionStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ProgressionStatus::REJECTED;
    }

    public function isApplied(): bool
    {
        return $this->status === ProgressionStatus::APPLIED;
    }

    public function isCancelled(): bool
    {
        return $this->status === ProgressionStatus::CANCELLED;
    }

    public function isNeedsReview(): bool
    {
        return $this->status === ProgressionStatus::NEEDS_REVIEW;
    }

    public function isPromotion(): bool
    {
        return $this->type === ProgressionType::PROMOTION;
    }

    public function isGraduation(): bool
    {
        return $this->type === ProgressionType::GRADUATION;
    }

    public function isRetention(): bool
    {
        return $this->type === ProgressionType::RETENTION;
    }
}
