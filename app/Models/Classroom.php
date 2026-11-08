<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'term_id',
        'grade_level',
        'section',
        'roster_version',
    ];

    protected $appends = [
        'name',
        'full_name',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'roster_version' => 'integer',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class);
    }

    public function activeHomeroom(): HasOne
    {
        return $this->hasOne(HomeroomAssignment::class)->where('status', 'active');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class)->where('status', 'active');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'classroom_id', 'subject_id')
            ->withTimestamps();
    }

    public function incrementRosterVersion(): void
    {
        $this->increment('roster_version');
    }

    public function getNameAttribute(): string
    {
        return "{$this->grade_level}-{$this->section}";
    }

    public function getFullNameAttribute(): string
    {
        return "Kelas {$this->grade_level} {$this->section}";
    }
}
