<?php

namespace App\Models;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'term_id',
        'grading_policy_id',
        'homeroom_assignment_id',
        'revision',
        'supersedes_id',
        'status',
        'availability',
        'version',
        'source_version',
        'homeroom_note',
        'calculation_version',
        'generated_at',
        'finalized_at',
        'finalized_by',
        'published_at',
        'published_by',
        'revoked_at',
        'revoked_by',
        'lifecycle_reason',
        'attendance_summary',
        'identity_snapshot',
        'private_file_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReportCardStatus::class,
            'availability' => ReportCardAvailability::class,
            'revision' => 'integer',
            'version' => 'integer',
            'generated_at' => 'datetime',
            'finalized_at' => 'datetime',
            'published_at' => 'datetime',
            'revoked_at' => 'datetime',
            'attendance_summary' => 'array',
            'identity_snapshot' => 'array',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function gradingPolicy(): BelongsTo
    {
        return $this->belongsTo(GradingPolicy::class);
    }

    public function homeroomAssignment(): BelongsTo
    {
        return $this->belongsTo(HomeroomAssignment::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_id');
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReportCardItem::class);
    }

    public function extracurricularAssessments(): HasMany
    {
        return $this->hasMany(ExtracurricularAssessment::class);
    }

    public function p5Assessments(): HasMany
    {
        return $this->hasMany(P5Assessment::class);
    }

    public function progressions(): HasMany
    {
        return $this->hasMany(StudentProgression::class, 'source_report_card_id');
    }

    public function isDraft(): bool
    {
        return $this->status === ReportCardStatus::DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this->status === ReportCardStatus::FINALIZED;
    }

    public function isPublished(): bool
    {
        return $this->status === ReportCardStatus::PUBLISHED;
    }

    public function isRevoked(): bool
    {
        return $this->status === ReportCardStatus::REVOKED;
    }

    public function isCurrent(): bool
    {
        return $this->availability === ReportCardAvailability::CURRENT;
    }

    public function needsRevision(): bool
    {
        return $this->availability === ReportCardAvailability::NEEDS_REVISION;
    }

    public function isSuperseded(): bool
    {
        return $this->availability === ReportCardAvailability::SUPERSEDED;
    }
}
