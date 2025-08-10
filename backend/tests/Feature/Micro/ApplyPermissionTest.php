<?php

namespace Tests\Feature\Micro;


use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;

class ApplyPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_micro_applicant_can_hit_apply_route_but_needs_valid_data(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('micro_applicant');

        // No payload on purpose: we only care that it's NOT 403 (permission passed).
        $this->actingAs($user)
            ->postJson('/api/microcredentials/apply', []) // changed to postJson()
            ->assertStatus(422); // expect validation error, not Forbidden
    }


    public function test_user_without_permission_is_forbidden_on_apply(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create(); // no role/permission

        $this->actingAs($user)
            ->post('/api/microcredentials/apply', [])
            ->assertForbidden();
    }
}
