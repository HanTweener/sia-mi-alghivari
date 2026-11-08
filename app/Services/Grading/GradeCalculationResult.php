<?php

namespace App\Services\Grading;

use Illuminate\Contracts\Support\Arrayable;

class GradeCalculationResult implements Arrayable
{
    /**
     * @param  array<string, ComponentCalculationResult>  $components
     * @param  array<int, string>  $missingComponents
     */
    public function __construct(
        public readonly bool $isComplete,
        public readonly string $kkm,
        public readonly ?string $finalScore,
        public readonly ?string $finalScoreRaw,
        public readonly string $partialScore,
        public readonly string $partialScoreRaw,
        public readonly ?bool $passedKkm,
        public readonly string $kkmStatus,
        public readonly array $components,
        public readonly array $missingComponents,
        public readonly bool $hasDisplayDifference,
        public readonly ?string $displaySumDifference,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_complete' => $this->isComplete,
            'kkm' => $this->kkm,
            'final_score' => $this->finalScore,
            'final_score_raw' => $this->finalScoreRaw,
            'partial_score' => $this->partialScore,
            'partial_score_raw' => $this->partialScoreRaw,
            'passed_kkm' => $this->passedKkm,
            'kkm_status' => $this->kkmStatus,
            'components' => array_map(
                fn (ComponentCalculationResult $c) => $c->toArray(),
                $this->components
            ),
            'missing_components' => $this->missingComponents,
            'has_display_difference' => $this->hasDisplayDifference,
            'display_sum_difference' => $this->displaySumDifference,
        ];
    }
}
