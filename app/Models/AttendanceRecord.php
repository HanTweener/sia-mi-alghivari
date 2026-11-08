<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';

    public const STATUS_EXCUSED = 'excused';

    public const STATUS_SICK = 'sick';

    public const STATUS_ABSENT = 'absent';

    protected $fillable = [
        'attendance_session_id',
        'enrollment_id',
        'status',
        'recorded_by',
        'delegation_id',
    ];

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(DataEntryDelegation::class, 'delegation_id');
    }

    public function isPresent(): bool
    {
        return $this->status === self::STATUS_PRESENT;
    }

    public function isExcused(): bool
    {
        return $this->status === self::STATUS_EXCUSED;
    }

    public function isSick(): bool
    {
        return $this->status === self::STATUS_SICK;
    }

    public function isAbsent(): bool
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PRESENT => 'Hadir',
            self::STATUS_EXCUSED => 'Izin',
            self::STATUS_SICK => 'Sakit',
            self::STATUS_ABSENT => 'Alpa',
            default => $this->status,
        };
    }
}
