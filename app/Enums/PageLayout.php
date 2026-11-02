<?php

namespace App\Enums;

enum PageLayout: string
{
    case Layout1 = 'layout_1';
    case Layout2 = 'layout_2';
    case Layout3 = 'layout_3';

    public function label(): string
    {
        return match ($this) {
            self::Layout1 => 'Layout 1',
            self::Layout2 => 'Layout 2',
            self::Layout3 => 'Layout 3',
        };
    }

    public function component(): string
    {
        return match ($this) {
            self::Layout1 => 'Public/Pages/Layout1',
            self::Layout2 => 'Public/Pages/Layout2',
            self::Layout3 => 'Public/Pages/Layout3',
        };
    }

    public function hasCards(): bool
    {
        return $this === self::Layout2;
    }

    public function hasSlides(): bool
    {
        return $this === self::Layout2;
    }

    /**
     * @return list<array{id: string, name: string, has_cards: bool, has_slides: bool}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'id' => $case->value,
                'name' => $case->label(),
                'has_cards' => $case->hasCards(),
                'has_slides' => $case->hasSlides(),
            ],
            self::cases(),
        );
    }
}
