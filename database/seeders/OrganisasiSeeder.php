<?php

namespace Database\Seeders;

use App\Models\OrganisasiModel;
use Illuminate\Database\Seeder;
use RuntimeException;

class OrganisasiSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/daftar_unit_kerja.csv');
        $file = fopen($path, 'r');

        if ($file === false) {
            throw new RuntimeException("Berkas unit kerja tidak ditemukan: {$path}");
        }

        try {
            fgetcsv($file, separator: ';');

            while (($row = fgetcsv($file, separator: ';')) !== false) {
                $code = trim($row[0] ?? '');
                $name = trim($row[1] ?? '');

                if ($code === '' || $name === '') {
                    continue;
                }

                OrganisasiModel::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'type' => $this->typeFromName($name),
                        'is_active' => true,
                    ],
                );
            }
        } finally {
            fclose($file);
        }
    }

    private function typeFromName(string $name): string
    {
        $normalized = strtoupper($name);

        return match (true) {
            str_starts_with($normalized, 'DINAS') => 'Dinas',
            str_starts_with($normalized, 'PUSKESMAS'), str_starts_with($normalized, 'PPK BLUD') => 'Puskesmas',
            str_starts_with($normalized, 'RUMAH SAKIT') => 'Rumah Sakit',
            str_starts_with($normalized, 'UPT') => 'UPT',
            str_starts_with($normalized, 'SATUAN') => 'Satuan',
            str_starts_with($normalized, 'BADAN') => 'Badan',
            str_starts_with($normalized, 'SEKRETARIAT') => 'Sekretariat',
            str_starts_with($normalized, 'INSPEKTORAT') => 'Inspektorat',
            str_starts_with($normalized, 'KECAMATAN') => 'Kecamatan',
            str_starts_with($normalized, 'KELURAHAN') => 'Kelurahan',
            default => ucfirst(strtolower(strtok($normalized, ' ') ?: $normalized)),
        };
    }
}
