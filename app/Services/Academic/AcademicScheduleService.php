<?php

namespace App\Services\Academic;

use App\Models\ClassSubject;
use App\Models\ScheduleSlot;
use InvalidArgumentException;

class AcademicScheduleService
{
    /**
     * Validate schedule conflict for classroom and teacher.
     *
     * @throws InvalidArgumentException
     */
    public function validateNoConflict(
        ClassSubject $classSubject,
        int $weekday,
        string $startsAt,
        string $endsAt,
        ?int $ignoreSlotId = null
    ): void {
        if ($startsAt >= $endsAt) {
            throw new InvalidArgumentException('Waktu mulai harus lebih awal daripada waktu selesai.');
        }

        // 1. Check classroom conflict: Classroom cannot have overlapping slots on same weekday
        $classroomConflict = ScheduleSlot::whereHas('classSubject', function ($q) use ($classSubject) {
            $q->where('classroom_id', $classSubject->classroom_id);
        })
            ->where('weekday', $weekday)
            ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            })
            ->with('classSubject.subject')
            ->first();

        if ($classroomConflict) {
            $subjectName = $classroomConflict->classSubject?->subject?->name ?? 'Mapel';
            throw new InvalidArgumentException("Bentrok jadwal kelas: Kelas ini sudah memiliki jadwal {$subjectName} pada hari {$classroomConflict->weekday_name} ({$classroomConflict->starts_at} - {$classroomConflict->ends_at}).");
        }

        // 2. Check teacher conflict: Teacher cannot teach in two places simultaneously
        $activeAssignment = $classSubject->activeTeachingAssignment;
        if ($activeAssignment) {
            $teacherConflict = ScheduleSlot::whereHas('classSubject.activeTeachingAssignment', function ($q) use ($activeAssignment) {
                $q->where('teacher_id', $activeAssignment->teacher_id);
            })
                ->where('weekday', $weekday)
                ->when($ignoreSlotId, fn ($q) => $q->where('id', '!=', $ignoreSlotId))
                ->where(function ($q) use ($startsAt, $endsAt) {
                    $q->where('starts_at', '<', $endsAt)
                        ->where('ends_at', '>', $startsAt);
                })
                ->with(['classSubject.classroom', 'classSubject.subject'])
                ->first();

            if ($teacherConflict) {
                $teacherName = $activeAssignment->teacher?->name ?? 'Guru';
                $conflictClass = $teacherConflict->classSubject?->classroom?->full_name ?? 'kelas lain';
                throw new InvalidArgumentException("Bentrok jadwal guru: {$teacherName} sudah memiliki jadwal mengajar di {$conflictClass} pada hari {$teacherConflict->weekday_name} ({$teacherConflict->starts_at} - {$teacherConflict->ends_at}).");
            }
        }
    }
}
