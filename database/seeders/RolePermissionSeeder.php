<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'dashboard.view',
        'users.manage',
        'karyawan.manage',
        'pelanggan.manage',
        'katalog.view',
        'katalog.manage',
        'nota.view',
        'nota.create',
        'nota.update',
        'nota.delete',
        'nota.export',
        'loyalitas.manage',
        'promosi.manage',
        'pengaturan.manage',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        UserRole::Admin->value => self::PERMISSIONS,
        UserRole::Karyawan->value => [
            'dashboard.view',
            'pelanggan.manage',
            'katalog.view',
            'katalog.manage',
            'nota.view',
            'nota.create',
            'nota.update',
            'nota.export',
        ],
        UserRole::Pelanggan->value => [],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLE_PERMISSIONS as $role => $permissions) {
            $models = Permission::query()->whereIn('name', $permissions)->where('guard_name', 'web')->get();

            Role::findOrCreate($role, 'web')->syncPermissions($models);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
