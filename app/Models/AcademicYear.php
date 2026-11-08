<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function sourceProgressions(): HasMany
    {
        return $this->hasMany(StudentProgression::class, 'source_academic_year_id');
    }

    public function targetProgressions(): HasMany
    {
        return $this->hasMany(StudentProgression::class, 'target_academic_year_id');
    }
}
