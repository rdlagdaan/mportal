<?php

namespace App\Policies\;

use App\Models\Mobile\Users;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Users $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Users $user): bool
    {
        return true;
    }


}