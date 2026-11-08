<?php

namespace App\Services\Grading;

use App\Models\GradingPolicy;
use App\Models\GradingPolicyComponent;
use InvalidArgumentException;

class GradingCalculator
{
    private const SCALE = 10;

    /**
     * Calculate subject grades, component contributions, and completeness status.
     *
     * @param  GradingPolicy|array<string, mixed>  $policy
     * @param  array<string|int, mixed>  $scoresByComponent
     * @param  array<string, mixed>|null  $attendance
     *
     * @throws InvalidArgumentException
     */
    public function calculate(
        GradingPolicy|array $policy,
        array $scoresByComponent = [],
        ?array $attendance = null
    ): GradeCalculationResult {
        $extracted = $this->extractPolicyData($policy);
        $kkm = $extracted['kkm'];
        $policyComponents = $extracted['components'];

        $calculatedComponents = [];
        $missingComponents = [];
        $isComplete = true;
        $partialScoreRaw = '0.00';
        $displaySum = '0.00';

        foreach ($policyComponents as $comp) {
            $code = $comp['code'];
            $name = $comp['name'];
            $sourceType = $comp['source_type'];
            $weightPercentage = $comp['weight_percentage'];
            $scoringComponentId = $comp['scoring_component_id'] ?? null;

            if ($sourceType === 'attendance') {
                $attData = $attendance
                    ?? $scoresByComponent[$code]
                    ?? ($scoringComponentId ? ($scoresByComponent[$scoringComponentId] ?? null) : null);

                $result = $this->calculateAttendanceComponent(
                    $code,
                    $name,
                    $weightPercentage,
                    is_array($attData) ? $attData : null
                );
            } else {
                $rawScores = $scoresByComponent[$code]
                    ?? ($scoringComponentId ? ($scoresByComponent[$scoringComponentId] ?? []) : []);

                if (! is_array($rawScores)) {
                    $rawScores = [$rawScores];
                }

                $result = $this->calculateScoreComponent(
                    $code,
                    $name,
                    $weightPercentage,
                    $rawScores
                );
            }

            $calculatedComponents[$code] = $result;

            if ($result->contribution !== null) {
                $partialScoreRaw = bcadd($partialScoreRaw, $result->contribution, self::SCALE);
                $displaySum = bcadd($displaySum, (string) $result->contributionRounded, 2);
            }

            // A component with positive weight that has no data blocks report card completion
            $hasPositiveWeight = bccomp($weightPercentage, '0.00', 2) > 0;
            if ($hasPositiveWeight && $result->isEmpty) {
                $isComplete = false;
                $missingComponents[] = $code;
            }
        }

        $partialScore = self::roundHalfUp($partialScoreRaw, 2);

        if ($isComplete) {
            // Q7.3: Maintain full precision of contributions, then round final total half-up to two decimals
            $finalScoreRaw = $partialScoreRaw;
            $finalScore = self::roundHalfUp($finalScoreRaw, 2);

            // Q7.4: Exact decimal comparison against policy KKM without epsilon
            $passedKkm = bccomp($finalScore, $kkm, 2) >= 0;
            $kkmStatus = $passedKkm ? 'Mencapai KKM' : 'Belum mencapai KKM';

            $displayDiff = bcsub($finalScore, $displaySum, 2);
            $hasDisplayDifference = bccomp($displayDiff, '0.00', 2) !== 0;
            $displaySumDifference = $hasDisplayDifference ? $displayDiff : null;
        } else {
            $finalScoreRaw = null;
            $finalScore = null;
            $passedKkm = null;
            $kkmStatus = 'Belum tersedia';
            $hasDisplayDifference = false;
            $displaySumDifference = null;
        }

        return new GradeCalculationResult(
            isComplete: $isComplete,
            kkm: $kkm,
            finalScore: $finalScore,
            finalScoreRaw: $finalScoreRaw,
            partialScore: $partialScore,
            partialScoreRaw: $partialScoreRaw,
            passedKkm: $passedKkm,
            kkmStatus: $kkmStatus,
            components: $calculatedComponents,
            missingComponents: $missingComponents,
            hasDisplayDifference: $hasDisplayDifference,
            displaySumDifference: $displaySumDifference,
        );
    }

    /**
     * Calculate score component metrics.
     *
     * @param  array<int, mixed>  $rawScores
     */
    public function calculateScoreComponent(
        string $code,
        string $name,
        string $weightPercentage,
        array $rawScores
    ): ComponentCalculationResult {
        $parsed = $this->parseAndValidateScores($rawScores);
        $validScores = $parsed['valid_scores'];
        $blankCount = $parsed['blank_count'];
        $scoreCount = count($validScores);

        if ($scoreCount === 0) {
            return new ComponentCalculationResult(
                code: $code,
                name: $name,
                sourceType: 'score',
                weightPercentage: $weightPercentage,
                isEmpty: true,
                scoreCount: 0,
                blankCount: $blankCount,
                scores: [],
                average: null,
                averageRounded: null,
                contribution: null,
                contributionRounded: null,
            );
        }

        $sum = '0.00';
        foreach ($validScores as $score) {
            $sum = bcadd($sum, $score, self::SCALE);
        }

        $average = bcdiv($sum, (string) $scoreCount, self::SCALE);
        $averageRounded = self::roundHalfUp($average, 2);

        $contribution = bcdiv(bcmul($average, $weightPercentage, self::SCALE), '100.00', self::SCALE);
        $contributionRounded = self::roundHalfUp($contribution, 2);

        return new ComponentCalculationResult(
            code: $code,
            name: $name,
            sourceType: 'score',
            weightPercentage: $weightPercentage,
            isEmpty: false,
            scoreCount: $scoreCount,
            blankCount: $blankCount,
            scores: $validScores,
            average: $average,
            averageRounded: $averageRounded,
            contribution: $contribution,
            contributionRounded: $contributionRounded,
        );
    }

    /**
     * Calculate attendance component metrics.
     *
     * @param  array<string, mixed>|null  $attendanceData
     */
    public function calculateAttendanceComponent(
        string $code,
        string $name,
        string $weightPercentage,
        ?array $attendanceData
    ): ComponentCalculationResult {
        if ($attendanceData === null) {
            return new ComponentCalculationResult(
                code: $code,
                name: $name,
                sourceType: 'attendance',
                weightPercentage: $weightPercentage,
                isEmpty: true,
                scoreCount: 0,
                blankCount: 0,
                scores: [],
                average: null,
                averageRounded: null,
                contribution: null,
                contributionRounded: null,
                presentCount: 0,
                totalSessions: 0,
                attendancePercentage: null,
            );
        }

        $present = (int) ($attendanceData['present'] ?? $attendanceData['present_count'] ?? 0);
        $total = (int) ($attendanceData['total'] ?? $attendanceData['total_sessions'] ?? $attendanceData['total_finalized_sessions'] ?? 0);

        if ($present < 0 || $total < 0) {
            throw new InvalidArgumentException('Jumlah kehadiran tidak boleh bernilai negatif.');
        }

        if ($present > $total) {
            throw new InvalidArgumentException("Jumlah hadir ({$present}) tidak boleh melebihi total pertemuan ({$total}).");
        }

        if ($total === 0) {
            return new ComponentCalculationResult(
                code: $code,
                name: $name,
                sourceType: 'attendance',
                weightPercentage: $weightPercentage,
                isEmpty: true,
                scoreCount: 0,
                blankCount: 0,
                scores: [],
                average: null,
                averageRounded: null,
                contribution: null,
                contributionRounded: null,
                presentCount: $present,
                totalSessions: 0,
                attendancePercentage: null,
            );
        }

        // Attendance percentage is present / total * 100
        $percentage = bcdiv(bcmul((string) $present, '100.00', self::SCALE), (string) $total, self::SCALE);
        $percentageRounded = self::roundHalfUp($percentage, 2);

        $contribution = bcdiv(bcmul($percentage, $weightPercentage, self::SCALE), '100.00', self::SCALE);
        $contributionRounded = self::roundHalfUp($contribution, 2);

        return new ComponentCalculationResult(
            code: $code,
            name: $name,
            sourceType: 'attendance',
            weightPercentage: $weightPercentage,
            isEmpty: false,
            scoreCount: 1,
            blankCount: 0,
            scores: [$percentageRounded],
            average: $percentage,
            averageRounded: $percentageRounded,
            contribution: $contribution,
            contributionRounded: $contributionRounded,
            presentCount: $present,
            totalSessions: $total,
            attendancePercentage: $percentageRounded,
        );
    }

    /**
     * Exact decimal string half-up rounding.
     */
    public static function roundHalfUp(string $number, int $precision = 2): string
    {
        if (! str_contains($number, '.')) {
            return $number.'.'.str_repeat('0', $precision);
        }

        [$int, $dec] = explode('.', $number, 2);

        if (strlen($dec) <= $precision) {
            return $int.'.'.str_pad($dec, $precision, '0');
        }

        $truncated = $int.'.'.substr($dec, 0, $precision);
        $nextDigit = (int) $dec[$precision];

        if ($nextDigit >= 5) {
            $step = '0.'.str_repeat('0', $precision - 1).'1';

            return str_starts_with($int, '-')
                ? bcsub($truncated, $step, $precision)
                : bcadd($truncated, $step, $precision);
        }

        return $truncated;
    }

    /**
     * Exact decimal KKM comparison without epsilon.
     */
    public static function compareKkm(string $finalScore, string $kkm): bool
    {
        return bccomp($finalScore, $kkm, 2) >= 0;
    }

    /**
     * Parse raw scores into valid decimal strings while tracking blanks.
     *
     * @param  array<int, mixed>  $rawScores
     * @return array{valid_scores: array<int, string>, blank_count: int}
     *
     * @throws InvalidArgumentException
     */
    private function parseAndValidateScores(array $rawScores): array
    {
        $validScores = [];
        $blankCount = 0;

        foreach ($rawScores as $item) {
            if (is_object($item) && isset($item->score)) {
                $item = $item->score;
            } elseif (is_array($item) && array_key_exists('score', $item)) {
                $item = $item['score'];
            }

            if ($item === null || $item === '') {
                $blankCount++;

                continue;
            }

            $str = trim((string) $item);
            if ($str === '') {
                $blankCount++;

                continue;
            }

            if (! is_numeric($str)) {
                throw new InvalidArgumentException("Skor harus berupa nilai numerik valid atau blank: {$str}");
            }

            // Q7.2: Reject scores with more than two decimal places
            if (str_contains($str, '.')) {
                $decimals = strlen(explode('.', $str)[1]);
                if ($decimals > 2) {
                    throw new InvalidArgumentException("Skor maksimal memiliki dua angka desimal: {$str}");
                }
            }

            // Q7.2: Reject scores outside 0-100 range
            if (bccomp($str, '0.00', 4) < 0 || bccomp($str, '100.00', 4) > 0) {
                throw new InvalidArgumentException("Skor harus berada dalam rentang 0-100: {$str}");
            }

            $validScores[] = str_contains($str, '.') ? $str : $str.'.00';
        }

        return [
            'valid_scores' => $validScores,
            'blank_count' => $blankCount,
        ];
    }

    /**
     * Extract components and KKM from GradingPolicy model or array.
     *
     * @param  GradingPolicy|array<string, mixed>  $policy
     * @return array{kkm: string, components: array<int, array<string, mixed>>}
     */
    private function extractPolicyData(GradingPolicy|array $policy): array
    {
        if ($policy instanceof GradingPolicy) {
            $kkm = self::roundHalfUp((string) $policy->kkm, 2);
            $components = [];

            /** @var GradingPolicyComponent $comp */
            foreach ($policy->components()->with('scoringComponent')->get() as $comp) {
                $sc = $comp->scoringComponent;
                $components[] = [
                    'code' => $sc?->code ?? "comp_{$comp->id}",
                    'name' => $sc?->name ?? 'Komponen',
                    'source_type' => $sc?->source_type ?? 'score',
                    'weight_percentage' => self::roundHalfUp((string) $comp->weight_percentage, 2),
                    'scoring_component_id' => $comp->scoring_component_id,
                ];
            }

            return ['kkm' => $kkm, 'components' => $components];
        }

        $kkm = self::roundHalfUp((string) ($policy['kkm'] ?? '0.00'), 2);
        $components = [];

        foreach ($policy['components'] ?? [] as $comp) {
            $components[] = [
                'code' => (string) ($comp['code'] ?? 'comp'),
                'name' => (string) ($comp['name'] ?? $comp['code'] ?? 'Komponen'),
                'source_type' => (string) ($comp['source_type'] ?? 'score'),
                'weight_percentage' => self::roundHalfUp((string) ($comp['weight_percentage'] ?? '0.00'), 2),
                'scoring_component_id' => $comp['scoring_component_id'] ?? null,
            ];
        }

        return ['kkm' => $kkm, 'components' => $components];
    }
}
