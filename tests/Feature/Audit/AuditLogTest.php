<?php

namespace Tests\Feature\Audit;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $masterAdmin;

    protected User $academicAdmin;

    protected User $teacher;

    protected User $guardian;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masterAdmin = User::create([
            'name' => 'Master Admin',
            'username' => 'admin',
            'must_change_password' => false,
            'email' => 'admin@madrasah.test',
            'password' => 'secret123456789',
            'password_changed_at' => now(),
        ]);
        $this->masterAdmin->assignRole(Role::MASTER_ADMIN);

        $this->academicAdmin = User::create([
            'name' => 'Academic Admin',
            'username' => 'akademik',
            'must_change_password' => false,
            'email' => 'akademik@madrasah.test',
            'password' => 'secret123456789',
            'password_changed_at' => now(),
        ]);
        $this->academicAdmin->assignRole(Role::ACADEMIC_ADMIN);

        $this->teacher = User::create([
            'name' => 'Guru Ahmad',
            'username' => 'guru.ahmad',
            'must_change_password' => false,
            'email' => 'ahmad@madrasah.test',
            'password' => 'secret123456789',
            'password_changed_at' => now(),
        ]);
        $this->teacher->assignRole(Role::TEACHER);

        $this->guardian = User::create([
            'name' => 'Wali Hasan',
            'username' => 'wali.hasan',
            'must_change_password' => false,
            'email' => 'hasan@madrasah.test',
            'password' => 'secret123456789',
            'password_changed_at' => now(),
        ]);
        $this->guardian->assignRole(Role::GUARDIAN);
    }

    public function test_master_admin_can_view_audit_logs_list_and_details(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->masterAdmin->id,
            'action' => 'UPDATE',
            'entity_type' => User::class,
            'entity_id' => (string) $this->academicAdmin->id,
            'scope' => AuditScope::USER,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => ['email' => 'newakademik@madrasah.test'],
            'reason' => 'Perubahan email staf',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->masterAdmin)->get('/audit-logs');
        $response->assertStatus(200);

        $jsonResponse = $this->actingAs($this->masterAdmin)->getJson('/audit-logs');
        $jsonResponse->assertStatus(200)
            ->assertJsonFragment(['action' => 'UPDATE', 'entity_id' => (string) $this->academicAdmin->id]);

        $detailResponse = $this->actingAs($this->masterAdmin)->getJson("/audit-logs/{$log->id}");
        $detailResponse->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.action', 'UPDATE');
    }

    public function test_academic_admin_can_view_audit_logs_list_and_details(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->academicAdmin->id,
            'action' => 'CREATE',
            'entity_type' => 'App\Models\Classroom',
            'entity_id' => '10',
            'scope' => AuditScope::CLASSROOM,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => ['name' => '1-A'],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->academicAdmin)->get('/audit-logs');
        $response->assertStatus(200);

        $detailResponse = $this->actingAs($this->academicAdmin)->get("/audit-logs/{$log->id}");
        $detailResponse->assertStatus(200);
    }

    public function test_unauthorized_roles_cannot_view_audit_logs(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->masterAdmin->id,
            'action' => 'CREATE',
            'entity_type' => 'App\Models\Subject',
            'entity_id' => '5',
            'scope' => AuditScope::SUBJECT,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => ['name' => 'Bahasa Arab'],
            'created_at' => now(),
        ]);

        // Teacher
        $this->actingAs($this->teacher)->get('/audit-logs')->assertStatus(403);
        $this->actingAs($this->teacher)->get("/audit-logs/{$log->id}")->assertStatus(403);

        // Guardian
        $this->actingAs($this->guardian)->get('/audit-logs')->assertStatus(403);
        $this->actingAs($this->guardian)->get("/audit-logs/{$log->id}")->assertStatus(403);
    }

    public function test_audit_logs_can_be_filtered_by_scope_action_actor_and_dates(): void
    {
        AuditLog::create([
            'actor_user_id' => $this->masterAdmin->id,
            'action' => 'APPLY_PROMOTION',
            'entity_type' => 'StudentProgression',
            'entity_id' => '1',
            'scope' => AuditScope::PROGRESSION,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => ['status' => 'applied'],
            'created_at' => now()->subDays(5),
        ]);

        AuditLog::create([
            'actor_user_id' => $this->academicAdmin->id,
            'action' => 'PUBLISH_REPORT',
            'entity_type' => 'ReportCard',
            'entity_id' => '2',
            'scope' => AuditScope::REPORT,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => ['status' => 'published'],
            'created_at' => now(),
        ]);

        // Filter by scope
        $scopeRes = $this->actingAs($this->masterAdmin)->getJson('/audit-logs?scope=progression');
        $scopeRes->assertStatus(200);
        $this->assertCount(1, $scopeRes->json('data'));
        $this->assertEquals('APPLY_PROMOTION', $scopeRes->json('data.0.action'));

        // Filter by actor
        $actorRes = $this->actingAs($this->masterAdmin)->getJson("/audit-logs?actor_user_id={$this->academicAdmin->id}");
        $actorRes->assertStatus(200);
        $this->assertCount(1, $actorRes->json('data'));
        $this->assertEquals('PUBLISH_REPORT', $actorRes->json('data.0.action'));

        // Filter by action search
        $actionRes = $this->actingAs($this->masterAdmin)->getJson('/audit-logs?action=PROMOTION');
        $actionRes->assertStatus(200);
        $this->assertCount(1, $actionRes->json('data'));

        // Filter by date range (ends_on 2 days ago)
        $dateRes = $this->actingAs($this->masterAdmin)->getJson('/audit-logs?ends_on='.now()->subDays(2)->toDateString());
        $dateRes->assertStatus(200);
        $this->assertCount(1, $dateRes->json('data'));
        $this->assertEquals('APPLY_PROMOTION', $dateRes->json('data.0.action'));
    }

    public function test_sensitive_fields_in_allowed_changes_are_redacted(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->masterAdmin->id,
            'action' => 'RESET_PASSWORD',
            'entity_type' => User::class,
            'entity_id' => (string) $this->teacher->id,
            'scope' => AuditScope::USER,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => [
                'username' => 'guru.ahmad',
                'password' => 'secret_raw_hash',
                'nested' => [
                    'session_token' => 'xyz987token',
                    'note' => 'Safe value',
                ],
            ],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->masterAdmin)->getJson("/audit-logs/{$log->id}");
        $response->assertStatus(200);

        $changes = $response->json('data.allowed_changes');
        $this->assertEquals('guru.ahmad', $changes['username']);
        $this->assertEquals('[REDACTED]', $changes['password']);
        $this->assertEquals('[REDACTED]', $changes['nested']['session_token']);
        $this->assertEquals('Safe value', $changes['nested']['note']);
    }

    public function test_audit_logs_cannot_be_modified_or_deleted_via_routes_or_model(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->masterAdmin->id,
            'action' => 'SECURITY_ALERT',
            'entity_type' => 'System',
            'entity_id' => '1',
            'scope' => AuditScope::SYSTEM,
            'event_visibility' => AuditVisibility::INTERNAL,
            'allowed_changes' => ['alert' => 'Test'],
            'created_at' => now(),
        ]);

        // Attempt HTTP POST, PUT, DELETE routes
        $this->actingAs($this->masterAdmin)->post('/audit-logs', ['action' => 'HACK'])->assertStatus(405);
        $this->actingAs($this->masterAdmin)->put("/audit-logs/{$log->id}", ['action' => 'HACK'])->assertStatus(405);
        $this->actingAs($this->masterAdmin)->delete("/audit-logs/{$log->id}")->assertStatus(405);

        // Model immutable checks
        $this->expectException(LogicException::class);
        $log->update(['action' => 'TAMPERED']);
    }
}
