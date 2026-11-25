<?php

namespace App\Policies;

use App\Models\Barang;
use App\Models\User;

class BarangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('katalog.view');
    }

    public function create(User $user): bool
    {
        return $user->can('katalog.manage');
    }

    public function update(User $user, Barang $barang): bool
    {
        return $user->can('katalog.manage');
    }

    public function delete(User $user, Barang $barang): bool
    {
        return $user->can('katalog.manage');
    }
}
