<?php

namespace Tests\Feature\Academic;

use App\Enums\Role;
use App\Models\GradingPolicy;
use App\Models\ScoringComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $dataEntry;

    private ScoringComponent $assignment;

    private ScoringComponent $exam;

    private ScoringComponent $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::ACADEMIC_ADMIN);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole(Role::TEACHER);

        $this->dataEntry = User::factory()->create();
        $this->dataEntry->assignRole(Role::DATA_ENTRY_ADMIN);

        // Standard components
        $this->assignment = ScoringComponent::firstOrCreate(
            ['code' => 'assignment'],
            ['name' => 'Tugas', 'source_type' => 'score', 'sort_order' => 1, 'is_active' => true]
        );

        $this->exam = ScoringComponent::firstOrCreate(
            ['code' => 'exam'],
            ['name' => 'Ujian', 'source_type' => 'score', 'sort_order' => 2, 'is_active' => true]
        );

        $this->attendance = ScoringComponent::firstOrCreate(
            ['code' => 'attendance'],
            ['name' => 'Kehadiran', 'source_type' => 'attendance', 'sort_order' => 3, 'is_active' => true]
        );
    }

    public function test_master_and_academic_admin_can_view_grading_policies_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/academic/grading-policies');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Academic/GradingPolicies/Index'));
    }

    public function test_unauthorized_roles_cannot_manage_grading_policies(): void
    {
        // Teacher cannot create policy
        $this->actingAs($this->teacher)->post('/academic/grading-policies', [
            'kkm' => '70.00',
            'components' => [
                ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '50.00'],
                ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '50.00'],
            ],
        ])->assertForbidden();

        // Data Entry cannot create policy
        $this->actingAs($this->dataEntry)->post('/academic/grading-policies', [
            'kkm' => '70.00',
            'components' => [
                ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '50.00'],
                ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '50.00'],
            ],
        ])->assertForbidden();
    }

    public function test_admin_can_create_draft_grading_policy(): void
    {
        $response = $this->actingAs($this->admin)->post('/academic/grading-policies', [
            'kkm' => '75.00',
            'components' => [
                ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '40.00'],
                ['scoring_component_id' => $this->exam->id, 'weight_percentage' => '45.00'],
                ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '15.00'],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grading_policies', [
            'revision' => 1,
            'kkm' => 75.00,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $policy = GradingPolicy::first();
        $this->assertCount(3, $policy->components);
        $this->assertSame(100.0, $policy->totalWeight());
    }

    public function test_activating_policy_requires_total_weight_to_equal_exactly_100(): void
    {
        // Create draft with total weight = 90.00%
        $policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 70.00,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $policy->components()->createMany([
            ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '40.00'],
            ['scoring_component_id' => $this->exam->id, 'weight_percentage' => '50.00'],
        ]);

        $this->assertSame(90.0, $policy->totalWeight());

        // Attempt activation should fail
        $response = $this->actingAs($this->admin)->patch("/academic/grading-policies/{$policy->id}/activate");

        $response->assertSessionHasErrors(['activation']);
        $this->assertSame('draft', $policy->fresh()->status);
    }

    public function test_activating_policy_transitions_previous_active_policy_to_retired(): void
    {
        // 1. Setup Policy 1 as active
        $policy1 = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 70.00,
            'status' => 'active',
            'created_by' => $this->admin->id,
            'activated_by' => $this->admin->id,
            'activated_at' => now()->subMonths(6),
        ]);
        $policy1->components()->createMany([
            ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '50.00'],
            ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '50.00'],
        ]);

        $this->assertSame(1, GradingPolicy::where('status', 'active')->count());

        // 2. Setup Policy 2 as draft with total weight 100.00%
        $policy2 = GradingPolicy::create([
            'revision' => 2,
            'kkm' => 75.00,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);
        $policy2->components()->createMany([
            ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '40.00'],
            ['scoring_component_id' => $this->exam->id, 'weight_percentage' => '50.00'],
            ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '10.00'],
        ]);

        // 3. Activate Policy 2
        $response = $this->actingAs($this->admin)->patch("/academic/grading-policies/{$policy2->id}/activate");

        $response->assertSessionHasNoErrors();

        // Policy 1 is now retired
        $this->assertSame('retired', $policy1->fresh()->status);

        // Policy 2 is now active
        $this->assertSame('active', $policy2->fresh()->status);
        $this->assertSame($this->admin->id, $policy2->fresh()->activated_by);
        $this->assertNotNull($policy2->fresh()->activated_at);

        // Invariant: Exact 1 active policy globally
        $this->assertSame(1, GradingPolicy::where('status', 'active')->count());
    }

    public function test_active_and_retired_policies_cannot_be_updated(): void
    {
        $policy = GradingPolicy::create([
            'revision' => 1,
            'kkm' => 70.00,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put("/academic/grading-policies/{$policy->id}", [
            'kkm' => '80.00',
            'components' => [
                ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '50.00'],
                ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '50.00'],
            ],
        ]);

        $response->assertSessionHasErrors(['policy']);
        $this->assertEquals(70.00, $policy->fresh()->kkm);
    }

    public function test_precision_validation_rejects_more_than_two_decimal_places(): void
    {
        // KKM with 3 decimals is rejected (Q7.5)
        $response1 = $this->actingAs($this->admin)->post('/academic/grading-policies', [
            'kkm' => '70.555',
            'components' => [
                ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '50.00'],
                ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '50.00'],
            ],
        ]);
        $response1->assertSessionHasErrors(['kkm']);

        // Weight with 3 decimals is rejected (Q7.5)
        $response2 = $this->actingAs($this->admin)->post('/academic/grading-policies', [
            'kkm' => '70.00',
            'components' => [
                ['scoring_component_id' => $this->assignment->id, 'weight_percentage' => '50.123'],
                ['scoring_component_id' => $this->attendance->id, 'weight_percentage' => '49.877'],
            ],
        ]);
        $response2->assertSessionHasErrors(['components.0.weight_percentage']);
    }

    public function test_admin_can_manage_scoring_components_catalog(): void
    {
        // 1. Add new scoring component
        $response = $this->actingAs($this->admin)->post('/academic/scoring-components', [
            'code' => 'project',
            'name' => 'Proyek / Portofolio',
            'source_type' => 'score',
            'sort_order' => 6,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('scoring_components', [
            'code' => 'project',
            'name' => 'Proyek / Portofolio',
            'source_type' => 'score',
            'is_active' => true,
        ]);

        // 2. Duplicate attendance component is rejected
        $duplicateAttendance = $this->actingAs($this->admin)->post('/academic/scoring-components', [
            'code' => 'attendance_two',
            'name' => 'Presensi Tambahan',
            'source_type' => 'attendance',
        ]);
        $duplicateAttendance->assertSessionHasErrors(['source_type']);

        // 3. Toggle component active status
        $component = ScoringComponent::where('code', 'project')->first();
        $this->assertTrue($component->is_active);

        $toggleResponse = $this->actingAs($this->admin)->patch("/academic/scoring-components/{$component->id}/toggle");
        $toggleResponse->assertSessionHasNoErrors();
        $this->assertFalse($component->fresh()->is_active);
    }
}
