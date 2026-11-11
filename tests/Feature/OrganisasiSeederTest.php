<?php

namespace Tests\Feature;

use App\Models\OrganisasiModel;
use Database\Seeders\OrganisasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganisasiSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_unit_kerja_csv_is_imported_into_organisasis(): void
    {
        $this->seed(OrganisasiSeeder::class);

        $this->assertSame(71, OrganisasiModel::query()->count());
        $this->assertDatabaseHas('organisasis', [
            'code' => '1-01.0-00.0-00.01.01',
            'name' => 'DINAS PENDIDIKAN',
            'type' => 'Dinas',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('organisasis', [
            'code' => '1-02.0-00.0-00.01.16',
            'name' => 'PPK BLUD TELUK SEBONG',
            'type' => 'Puskesmas',
        ]);
        $this->assertDatabaseHas('organisasis', [
            'code' => '7-01.0-00.0-00.01.01',
            'name' => 'KECAMATAN BINTAN UTARA',
            'type' => 'Kecamatan',
        ]);

        $this->seed(OrganisasiSeeder::class);

        $this->assertSame(71, OrganisasiModel::query()->count());
    }
}
