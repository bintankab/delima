<?php

namespace Database\Seeders;

use App\Models\MenuModel;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        MenuModel::ensureHeader();
    }
}
