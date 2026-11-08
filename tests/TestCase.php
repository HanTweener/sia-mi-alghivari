<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $persistedUser = $user->fresh();
        $this->withSession([
            'session_generation' => $persistedUser->session_generation,
            'temporary_credential_version' => $persistedUser->must_change_password ? $persistedUser->credential_version : null,
        ]);

        return parent::actingAs($user, $guard);
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.limiter' => 'array']);
        $this->withoutVite();
    }
}
