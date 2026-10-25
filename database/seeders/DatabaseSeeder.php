<?php

namespace Database\Seeders;

use App\Models\UserModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            InnovationCategorySeeder::class,
        ]);

        $superAdmin = UserModel::factory()->create([
            'name' => 'Super Admin',
            'email' => 'delima@bintankab.go.id',
            'password' => '12345678',
        ]);
        $superAdmin->assignRole('super-admin');
    }
}
