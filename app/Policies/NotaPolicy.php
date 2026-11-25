<?php

namespace App\Policies;

use App\Models\Nota;
use App\Models\User;

class NotaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('nota.view');
    }

    public function view(User $user, Nota $nota): bool
    {
        return $user->can('nota.view');
    }

    public function create(User $user): bool
    {
        return $user->can('nota.create');
    }

    public function update(User $user, Nota $nota): bool
    {
        return $user->can('nota.update');
    }

    public function delete(User $user, Nota $nota): bool
    {
        return $user->can('nota.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('nota.export');
    }
}
