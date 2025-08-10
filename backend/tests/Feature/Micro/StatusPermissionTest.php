<?php

namespace Tests\Feature\Micro;


use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;

class StatusPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_micro_applicant_can_view_status(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('micro_applicant');

        $this->actingAs($user)
            ->get('/api/microcredentials/status')
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create(); // no role/permission

        $this->actingAs($user)
            ->get('/api/microcredentials/status')
            ->assertForbidden();
    }
}
