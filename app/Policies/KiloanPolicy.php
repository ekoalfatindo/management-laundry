<?php

namespace App\Policies;

use App\Models\Kiloan;
use App\Models\User;

class KiloanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('katalog.view');
    }

    public function create(User $user): bool
    {
        return $user->can('katalog.manage');
    }

    public function update(User $user, Kiloan $kiloan): bool
    {
        return $user->can('katalog.manage');
    }

    public function delete(User $user, Kiloan $kiloan): bool
    {
        return $user->can('katalog.manage');
    }
}
