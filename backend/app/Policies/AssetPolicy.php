<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Assets\Asset;

class AssetPolicy
{
    // List / search
    public function viewAny(User $user): bool
    {
        return $user->can('fa.asset.view');
    }

    // View a single asset
    public function view(User $user, Asset $asset): bool
    {
        return $user->can('fa.asset.view');
    }

    public function create(User $user): bool
    {
        return $user->can('fa.asset.create');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('fa.asset.update');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('fa.asset.delete');
    }

    public function manageMaintenance(User $user, Asset $asset): bool
    {
        return $user->can('fa.maintenance.create') || $user->can('fa.maintenance.update');
    }

    // Optional: preview depreciation (tie to view)
    public function previewDepreciation(User $user, Asset $asset): bool
    {
        return $user->can('fa.asset.view');
    }
}
