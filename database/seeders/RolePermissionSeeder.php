<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public const ROLES = [
        'super-admin',
        'admin-opd',
        'reviewer',
        'asn',
        'bupati',
        'dprd',
        'publik',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
