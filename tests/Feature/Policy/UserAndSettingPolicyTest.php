<?php

namespace Tests\Feature\Policy;

use App\Enums\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Policies\SystemSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAndSettingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected UserPolicy $userPolicy;

    protected SystemSettingPolicy $settingPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userPolicy = new UserPolicy;
        $this->settingPolicy = new SystemSettingPolicy;
    }

    public function test_only_master_admin_can_create_users_and_manage_roles(): void
    {
        $master = User::create([
            'name' => 'Master Admin',
            'username' => 'master',
            'email' => 'master@example.com',
            'password' => 'secret123456789',
        ]);
        $master->assignRole(Role::MASTER_ADMIN);

        $teacher = User::create([
            'name' => 'Teacher User',
            'username' => 'teacher',
            'email' => 'teacher@example.com',
            'password' => 'secret123456789',
        ]);
        $teacher->assignRole(Role::TEACHER);

        $this->assertTrue($this->userPolicy->create($master));
        $this->assertTrue($this->userPolicy->manageRoles($master));

        $this->assertFalse($this->userPolicy->create($teacher));
        $this->assertFalse($this->userPolicy->manageRoles($teacher));
    }

    public function test_user_can_update_own_profile_but_cannot_update_others(): void
    {
        $user1 = User::create([
            'name' => 'User One',
            'username' => 'user1',
            'email' => 'user1@example.com',
            'password' => 'secret123456789',
        ]);

        $user2 = User::create([
            'name' => 'User Two',
            'username' => 'user2',
            'email' => 'user2@example.com',
            'password' => 'secret123456789',
        ]);

        $this->assertTrue($this->userPolicy->updateProfile($user1, $user1));
        $this->assertFalse($this->userPolicy->updateProfile($user1, $user2));
    }

    public function test_only_master_admin_can_manage_system_settings(): void
    {
        $master = User::create([
            'name' => 'Master Admin',
            'username' => 'master2',
            'email' => 'master2@example.com',
            'password' => 'secret123456789',
        ]);
        $master->assignRole(Role::MASTER_ADMIN);

        $guardian = User::create([
            'name' => 'Guardian User',
            'username' => 'guardian',
            'email' => 'guardian@example.com',
            'password' => 'secret123456789',
        ]);
        $guardian->assignRole(Role::GUARDIAN);

        $setting = SystemSetting::set('school_timezone', ['timezone' => 'Asia/Jakarta']);

        $this->assertTrue($this->settingPolicy->viewAny($master));
        $this->assertTrue($this->settingPolicy->view($master, $setting));
        $this->assertTrue($this->settingPolicy->update($master, $setting));

        $this->assertFalse($this->settingPolicy->viewAny($guardian));
        $this->assertFalse($this->settingPolicy->view($guardian, $setting));
        $this->assertFalse($this->settingPolicy->update($guardian, $setting));
    }
}
