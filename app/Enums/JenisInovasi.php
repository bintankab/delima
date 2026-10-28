<?php

namespace App\Enums;

enum JenisInovasi: string
{
    case Digital = 'digital';
    case NonDigital = 'non_digital';
    case Teknologi = 'teknologi';

    public function label(): string
    {
        return match ($this) {
            self::Digital => 'Digital',
            self::NonDigital => 'Non-Digital',
            self::Teknologi => 'Teknologi',
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
