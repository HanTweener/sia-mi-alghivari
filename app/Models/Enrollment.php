<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'classroom_id',
        'term_id',
        'starts_on',
        'ends_on',
        'status',
        'transferred_from_enrollment_id',
        'transfer_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeEligibleOn(Builder $query, Carbon|string $date): Builder
    {
        $target = $date instanceof Carbon ? $date->toDateString() : (string) $date;

        return $query->whereIn('status', ['active', 'transferred'])
            ->where('starts_on', '<=', $target)
            ->where(function ($q) use ($target) {
                $q->whereNull('ends_on')
                    ->orWhere('ends_on', '>', $target);
            });
    }

    public function transferredFrom(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'transferred_from_enrollment_id');
    }

    public function transferredTo(): HasOne
    {
        return $this->hasOne(Enrollment::class, 'transferred_from_enrollment_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function latestReportCard(): HasOne
    {
        return $this->hasOne(ReportCard::class)->latestOfMany('revision');
    }
}
