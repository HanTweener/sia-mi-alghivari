<?php

namespace Tests\Unit\Grading;

use App\Models\GradingPolicy;
use App\Models\GradingPolicyComponent;
use App\Models\ScoringComponent;
use App\Models\User;
use App\Services\Grading\GradingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GradingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private GradingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new GradingCalculator;
    }

    public function test_calculates_illustrative_example_with_exact_q7_precision(): void
    {
        // Canonical example from docs/ACADEMIC_FLOW.md line 87
        $policy = [
            'kkm' => '75.00',
            'components' => [
                ['code' => 'assignment', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '20.00'],
                ['code' => 'daily_test', 'name' => 'Ujian Harian', 'source_type' => 'score', 'weight_percentage' => '20.00'],
                ['code' => 'midterm', 'name' => 'UTS', 'source_type' => 'score', 'weight_percentage' => '25.00'],
                ['code' => 'final', 'name' => 'UAS', 'source_type' => 'score', 'weight_percentage' => '25.00'],
                ['code' => 'attendance', 'name' => 'Kehadiran', 'source_type' => 'attendance', 'weight_percentage' => '10.00'],
            ],
        ];

        $scores = [
            'assignment' => [90, 90, 100],
            'daily_test' => [80, null, 0],
            'midterm' => [85],
            'final' => [90],
        ];

        $attendance = [
            'present' => 18,
            'total' => 20,
            'sick' => 1,
            'excused' => 1,
            'absent' => 0,
        ];

        $result = $this->calculator->calculate($policy, $scores, $attendance);

        $this->assertTrue($result->isComplete);
        $this->assertSame('75.00', $result->kkm);

        // Verify per-component unrounded and rounded values
        $assignment = $result->components['assignment'];
        $this->assertSame('93.33', $assignment->averageRounded);
        $this->assertSame('18.67', $assignment->contributionRounded);
        $this->assertSame(3, $assignment->scoreCount);
        $this->assertSame(0, $assignment->blankCount);

        $dailyTest = $result->components['daily_test'];
        $this->assertSame('40.00', $dailyTest->averageRounded);
        $this->assertSame('8.00', $dailyTest->contributionRounded);
        $this->assertSame(2, $dailyTest->scoreCount);
        $this->assertSame(1, $dailyTest->blankCount);

        $midterm = $result->components['midterm'];
        $this->assertSame('85.00', $midterm->averageRounded);
        $this->assertSame('21.25', $midterm->contributionRounded);

        $final = $result->components['final'];
        $this->assertSame('90.00', $final->averageRounded);
        $this->assertSame('22.50', $final->contributionRounded);

        $att = $result->components['attendance'];
        $this->assertSame('90.00', $att->attendancePercentage);
        $this->assertSame('9.00', $att->contributionRounded);
        $this->assertSame(18, $att->presentCount);
        $this->assertSame(20, $att->totalSessions);

        // Q7.3: Total raw is 79.4166666666..., rounded half-up is exactly 79.42
        $this->assertStringStartsWith('79.416666', $result->finalScoreRaw);
        $this->assertSame('79.42', $result->finalScore);

        // Q7.4: KKM comparison (79.42 >= 75.00)
        $this->assertTrue($result->passedKkm);
        $this->assertSame('Mencapai KKM', $result->kkmStatus);
        $this->assertEmpty($result->missingComponents);
    }

    public function test_handles_blank_vs_zero_distinctly_without_redistributing_weight(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'assignment', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        // [90, null, 100] -> blank ignored, sum 190, count 2, avg 95.00
        $resultBlank = $this->calculator->calculate($policy, ['assignment' => [90, null, 100]]);
        $this->assertSame('95.00', $resultBlank->components['assignment']->averageRounded);
        $this->assertSame(2, $resultBlank->components['assignment']->scoreCount);
        $this->assertSame(1, $resultBlank->components['assignment']->blankCount);
        $this->assertSame('95.00', $resultBlank->finalScore);

        // [90, 0, 100] -> 0 counted, sum 190, count 3, avg 63.33
        $resultZero = $this->calculator->calculate($policy, ['assignment' => [90, 0, 100]]);
        $this->assertSame('63.33', $resultZero->components['assignment']->averageRounded);
        $this->assertSame(3, $resultZero->components['assignment']->scoreCount);
        $this->assertSame(0, $resultZero->components['assignment']->blankCount);
        $this->assertSame('63.33', $resultZero->finalScore);
    }

    public function test_all_zero_scores_are_valid_and_do_not_count_as_empty(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'exam', 'name' => 'Ujian', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        $result = $this->calculator->calculate($policy, ['exam' => [0, '0', '0.00']]);

        $this->assertTrue($result->isComplete);
        $this->assertFalse($result->components['exam']->isEmpty);
        $this->assertSame('0.00', $result->components['exam']->averageRounded);
        $this->assertSame('0.00', $result->finalScore);
        $this->assertFalse($result->passedKkm);
        $this->assertSame('Belum mencapai KKM', $result->kkmStatus);
    }

    public function test_empty_positive_weight_component_blocks_completeness(): void
    {
        $policy = [
            'kkm' => '75.00',
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '50.00'],
                ['code' => 'ujian', 'name' => 'Ujian', 'source_type' => 'score', 'weight_percentage' => '50.00'],
            ],
        ];

        // 'ujian' has positive weight 50% but all blanks
        $result = $this->calculator->calculate($policy, [
            'tugas' => [100, 100],
            'ujian' => [null, ''],
        ]);

        $this->assertFalse($result->isComplete);
        $this->assertNull($result->finalScore);
        $this->assertNull($result->finalScoreRaw);
        $this->assertNull($result->passedKkm);
        $this->assertSame('Belum tersedia', $result->kkmStatus);
        $this->assertContains('ujian', $result->missingComponents);

        // Partial preview is still available without redistributing weight
        $this->assertSame('50.00', $result->partialScore);
    }

    public function test_zero_weight_empty_component_does_not_block_completeness(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'wajib', 'name' => 'Komponen Wajib', 'source_type' => 'score', 'weight_percentage' => '100.00'],
                ['code' => 'ekstra', 'name' => 'Komponen Bobot 0', 'source_type' => 'score', 'weight_percentage' => '0.00'],
            ],
        ];

        $result = $this->calculator->calculate($policy, [
            'wajib' => [80],
            'ekstra' => [], // Empty data with 0 weight
        ]);

        $this->assertTrue($result->isComplete);
        $this->assertSame('80.00', $result->finalScore);
        $this->assertTrue($result->passedKkm);
        $this->assertEmpty($result->missingComponents);
        $this->assertTrue($result->components['ekstra']->isEmpty);
    }

    public function test_partial_blank_scores_do_not_block_completeness_when_at_least_one_numeric_exists(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        // 3 assessment items, 2 blank, 1 filled with 88
        $result = $this->calculator->calculate($policy, [
            'tugas' => [null, 88, ''],
        ]);

        $this->assertTrue($result->isComplete);
        $this->assertSame('88.00', $result->finalScore);
        $this->assertSame(1, $result->components['tugas']->scoreCount);
        $this->assertSame(2, $result->components['tugas']->blankCount);
    }

    public function test_attendance_with_zero_sessions_and_positive_weight_blocks_completeness(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '80.00'],
                ['code' => 'hadir', 'name' => 'Kehadiran', 'source_type' => 'attendance', 'weight_percentage' => '20.00'],
            ],
        ];

        $result = $this->calculator->calculate(
            $policy,
            ['tugas' => [90]],
            ['present' => 0, 'total' => 0]
        );

        $this->assertFalse($result->isComplete);
        $this->assertNull($result->finalScore);
        $this->assertContains('hadir', $result->missingComponents);
        $this->assertNull($result->components['hadir']->attendancePercentage);
    }

    public function test_q7_tie_breaking_half_up_precision_guarantees(): void
    {
        // 79.425 rounds to 79.43; 79.424 rounds to 79.42
        $this->assertSame('79.43', GradingCalculator::roundHalfUp('79.425', 2));
        $this->assertSame('79.42', GradingCalculator::roundHalfUp('79.424', 2));

        // 79.995 rounds to 80.00; 79.994 rounds to 79.99
        $this->assertSame('80.00', GradingCalculator::roundHalfUp('79.995', 2));
        $this->assertSame('79.99', GradingCalculator::roundHalfUp('79.994', 2));
    }

    public function test_q7_exact_kkm_comparison_on_boundary_conditions(): void
    {
        // Decision Q7.4: with KKM 80.00, result 79.995 rounds to 80.00 and reaches KKM
        $policy = [
            'kkm' => '80.00',
            'components' => [
                ['code' => 'c1', 'name' => 'C1', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        // Score crafted to yield 79.995 average
        // 159.99 / 2 = 79.995 -> rounds to 80.00 -> reaches KKM
        $resultPassing = $this->calculator->calculate($policy, ['c1' => ['79.99', '80.00']]);
        $this->assertSame('80.00', $resultPassing->finalScore);
        $this->assertTrue($resultPassing->passedKkm);
        $this->assertSame('Mencapai KKM', $resultPassing->kkmStatus);

        // Score 79.99 -> does not reach KKM
        $resultFailing = $this->calculator->calculate($policy, ['c1' => ['79.99']]);
        $this->assertSame('79.99', $resultFailing->finalScore);
        $this->assertFalse($resultFailing->passedKkm);
        $this->assertSame('Belum mencapai KKM', $resultFailing->kkmStatus);
    }

    public function test_detects_display_sum_difference_when_rounded_contributions_differ_from_final_score(): void
    {
        // Craft 3 components where unrounded contributions sum to a value that rounds differently
        // C1: avg 33.35 * 33.33% = 11.115555... -> rounded display 11.12
        // C2: avg 33.35 * 33.33% = 11.115555... -> rounded display 11.12
        // C3: avg 33.35 * 33.34% = 11.118890... -> rounded display 11.12
        // Sum of rounded display = 11.12 + 11.12 + 11.12 = 33.36
        // Unrounded total = 11.115555... + 11.115555... + 11.118890... = 33.3500... -> rounded final = 33.35
        // Discrepancy is 33.35 - 33.36 = -0.01
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'c1', 'name' => 'C1', 'source_type' => 'score', 'weight_percentage' => '33.33'],
                ['code' => 'c2', 'name' => 'C2', 'source_type' => 'score', 'weight_percentage' => '33.33'],
                ['code' => 'c3', 'name' => 'C3', 'source_type' => 'score', 'weight_percentage' => '33.34'],
            ],
        ];

        $scores = [
            'c1' => ['33.35'],
            'c2' => ['33.35'],
            'c3' => ['33.35'],
        ];

        $result = $this->calculator->calculate($policy, $scores);

        $this->assertTrue($result->hasDisplayDifference);
        $this->assertSame('-0.01', $result->displaySumDifference);
        $this->assertSame('33.35', $result->finalScore);
    }

    public function test_rejects_out_of_range_scores(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('rentang 0-100');
        $this->calculator->calculate($policy, ['tugas' => [100.01]]);
    }

    public function test_rejects_negative_scores(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('rentang 0-100');
        $this->calculator->calculate($policy, ['tugas' => [-0.01]]);
    }

    public function test_rejects_more_than_two_decimal_places_in_score(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'tugas', 'name' => 'Tugas', 'source_type' => 'score', 'weight_percentage' => '100.00'],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maksimal memiliki dua angka desimal');
        $this->calculator->calculate($policy, ['tugas' => ['87.555']]);
    }

    public function test_rejects_invalid_attendance_counts(): void
    {
        $policy = [
            'kkm' => '70.00',
            'components' => [
                ['code' => 'hadir', 'name' => 'Kehadiran', 'source_type' => 'attendance', 'weight_percentage' => '100.00'],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tidak boleh melebihi');
        $this->calculator->calculate($policy, [], ['present' => 12, 'total' => 10]);
    }

    public function test_calculates_with_eloquent_grading_policy_model(): void
    {
        $user = User::factory()->create();

        $tugas = ScoringComponent::firstOrCreate(
            ['code' => 'assignment'],
            ['name' => 'Tugas', 'source_type' => 'score', 'sort_order' => 1, 'is_active' => true]
        );

        $uas = ScoringComponent::firstOrCreate(
            ['code' => 'final'],
            ['name' => 'UAS', 'source_type' => 'score', 'sort_order' => 2, 'is_active' => true]
        );

        $policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => '75.00',
            'status' => 'active',
            'created_by' => $user->id,
            'activated_by' => $user->id,
            'activated_at' => now(),
        ]);

        GradingPolicyComponent::create([
            'grading_policy_id' => $policy->id,
            'scoring_component_id' => $tugas->id,
            'weight_percentage' => '40.00',
        ]);

        GradingPolicyComponent::create([
            'grading_policy_id' => $policy->id,
            'scoring_component_id' => $uas->id,
            'weight_percentage' => '60.00',
        ]);

        // Calculate using Eloquent model
        $result = $this->calculator->calculate($policy, [
            'assignment' => [90, 80], // avg 85.00 * 40% = 34.00
            'final' => [95],          // avg 95.00 * 60% = 57.00
        ]);

        $this->assertTrue($result->isComplete);
        $this->assertSame('91.00', $result->finalScore);
        $this->assertTrue($result->passedKkm);
        $this->assertSame('Mencapai KKM', $result->kkmStatus);
    }
}
