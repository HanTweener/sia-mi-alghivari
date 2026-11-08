<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'semester',
        'starts_on',
        'ends_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getSemesterNameAttribute(): string
    {
        return $this->semester === 1 ? 'Ganjil' : 'Genap';
    }

    public function getFullNameAttribute(): string
    {
        $year = $this->academicYear ? $this->academicYear->name : '';

        return "T.A. {$year} - Semester {$this->semester_name}";
    }
}
