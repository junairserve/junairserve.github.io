<?php

namespace App\Policies;

use App\Models\User;

class RolePolicy
{
    public function accessAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function accessStaff(User $user): bool
    {
        return in_array($user->role, ['admin', 'staff'], true);
    }

    public function accessInspector(User $user): bool
    {
        return in_array($user->role, ['admin', 'inspector'], true);
    }

    public function accessFactory(User $user): bool
    {
        return $user->role === 'factory';
    }
}
