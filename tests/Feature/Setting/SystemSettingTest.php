<?php

namespace Tests\Feature\Setting;

use App\Enums\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_admin_can_view_system_settings(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        $response = $this->actingAs($master)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/Settings/Index'));
    }

    public function test_non_master_is_forbidden_from_viewing_or_updating_settings(): void
    {
        $teacher = User::factory()->create();
        $teacher->assignRole(Role::TEACHER);

        $this->actingAs($teacher)->get('/admin/settings')->assertStatus(403);
        $this->actingAs($teacher)->put('/admin/settings', [
            'school_timezone' => 'Asia/Makassar',
            'school_name' => 'Unauthorized Edit',
        ])->assertStatus(403);
    }

    public function test_master_admin_can_update_school_timezone_and_info(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        $response = $this->actingAs($master)->put('/admin/settings', [
            'school_timezone' => 'Asia/Makassar', // WITA
            'school_name' => 'MI Alghivari Hebat',
            'school_address' => 'Jl. Rancabolang No. 45',
            'school_phone' => '022-7561234',
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertEquals('Asia/Makassar', SystemSetting::get('school_timezone'));
        $this->assertEquals('MI Alghivari Hebat', SystemSetting::get('school_name'));

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $master->id,
            'action' => 'UPDATE_SETTINGS',
        ]);
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $master = User::factory()->create();
        $master->assignRole(Role::MASTER_ADMIN);

        $response = $this->actingAs($master)->put('/admin/settings', [
            'school_timezone' => 'America/New_York', // Not in allowlist
            'school_name' => 'MI Alghivari',
        ]);

        $response->assertSessionHasErrors(['school_timezone']);
    }
}
