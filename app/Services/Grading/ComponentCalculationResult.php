<?php

namespace App\Services\Grading;

use Illuminate\Contracts\Support\Arrayable;

class ComponentCalculationResult implements Arrayable
{
    /**
     * @param  array<int, string>  $scores
     */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $sourceType,
        public readonly string $weightPercentage,
        public readonly bool $isEmpty,
        public readonly int $scoreCount,
        public readonly int $blankCount,
        public readonly array $scores,
        public readonly ?string $average,
        public readonly ?string $averageRounded,
        public readonly ?string $contribution,
        public readonly ?string $contributionRounded,
        public readonly ?int $presentCount = null,
        public readonly ?int $totalSessions = null,
        public readonly ?string $attendancePercentage = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'source_type' => $this->sourceType,
            'weight_percentage' => $this->weightPercentage,
            'is_empty' => $this->isEmpty,
            'score_count' => $this->scoreCount,
            'blank_count' => $this->blankCount,
            'scores' => $this->scores,
            'average' => $this->average,
            'average_rounded' => $this->averageRounded,
            'contribution' => $this->contribution,
            'contribution_rounded' => $this->contributionRounded,
            'present_count' => $this->presentCount,
            'total_sessions' => $this->totalSessions,
            'attendance_percentage' => $this->attendancePercentage,
        ];
    }
}
