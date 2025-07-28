<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    
    /**
     * Create and authenticate a user with the given role.
     *
     * @param string $role
     * @return \App\Models\User
     */
    protected function createAndAuthenticateUser($role = 'student')
    {
        $user = User::factory()->create([
            'role' => $role
        ]);
        
        Sanctum::actingAs($user);
        
        return $user;
    }
}
