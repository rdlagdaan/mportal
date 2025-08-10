<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            'micro.apply',
            'micro.view-status',
            'micro.manage-applicants',
        ];

        foreach ($perms as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $applicant = Role::findOrCreate('micro_applicant', 'web');
        $applicant->givePermissionTo(['micro.apply', 'micro.view-status']);
    }
}
