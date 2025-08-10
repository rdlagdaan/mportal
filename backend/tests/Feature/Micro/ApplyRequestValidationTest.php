<?php

namespace Tests\Feature\Micro;


use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;

class ApplyRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_fields_return_422_with_error_keys(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create()->assignRole('micro_applicant');

        $this->actingAs($user)
            ->postJson('/api/microcredentials/apply', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'last_name',
                'first_name',
                'mobile',
                'email',
                'password',
                'confirm_password',
                'consent',
            ]);
    }
}
