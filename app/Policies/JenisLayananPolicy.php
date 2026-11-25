<?php

namespace App\Policies;

use App\Models\JenisLayanan;
use App\Models\User;

class JenisLayananPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('katalog.view');
    }

    public function create(User $user): bool
    {
        return $user->can('katalog.manage');
    }

    public function update(User $user, JenisLayanan $jenisLayanan): bool
    {
        return $user->can('katalog.manage');
    }

    public function delete(User $user, JenisLayanan $jenisLayanan): bool
    {
        return $user->can('katalog.manage');
    }
}
