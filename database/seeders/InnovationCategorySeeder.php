<?php

namespace Database\Seeders;

use App\Models\InnovationCategoryModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InnovationCategorySeeder extends Seeder
{
    public const CATEGORIES = [
        'Masyarakat / Umum',
        'Pelayanan Publik',
        'Tata Kelola Pemerintahan',
        'Urusan Penyelenggaraan Daerah',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $name) {
            InnovationCategoryModel::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
