<?php

namespace App\Enums;

enum TahapanInovasi: string
{
    case Inisiasi = 'inisiasi';
    case UjiCoba = 'uji_coba';
    case Implementasi = 'implementasi';

    public function label(): string
    {
        return match ($this) {
            self::Inisiasi => 'Inisiasi',
            self::UjiCoba => 'Uji Coba',
            self::Implementasi => 'Implementasi',
        };
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['id' => $case->value, 'name' => $case->label()],
            self::cases(),
        );
    }
}
