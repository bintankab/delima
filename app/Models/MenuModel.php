<?php

namespace App\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'location'])]
#[UseFactory(MenuFactory::class)]
class MenuModel extends Model
{
    use HasFactory;

    protected $table = 'menus';

    public function items(): HasMany
    {
        return $this->hasMany(MenuItemModel::class, 'menu_id');
    }

    public static function ensureHeader(): self
    {
        $menu = static::query()->firstOrCreate(
            ['location' => 'header'],
            ['name' => 'Menu Atas'],
        );

        if ($menu->wasRecentlyCreated) {
            foreach (self::defaultItems() as $position => $item) {
                $menu->items()->create([
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'sort_order' => $position,
                    'opens_in_new_tab' => false,
                ]);
            }
        }

        return $menu;
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public static function defaultItems(): array
    {
        return [
            ['label' => 'Beranda', 'url' => '/'],
            ['label' => 'Kompetisi', 'url' => '/kompetisi'],
            ['label' => 'Riset', 'url' => '/riset'],
            ['label' => 'Inovasi', 'url' => '/inovasi'],
            ['label' => 'Pustaka', 'url' => '/pustaka'],
            ['label' => 'FAQ', 'url' => '/faq'],
        ];
    }

    /**
     * @return list<array{label: string, url: string, opens_in_new_tab: bool, children: list<array{label: string, url: string, opens_in_new_tab: bool}>}>
     */
    public function navigation(): array
    {
        return $this->items()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (MenuItemModel $item): array => [
                'label' => $item->label,
                'url' => $item->url,
                'opens_in_new_tab' => $item->opens_in_new_tab,
                'children' => $item->children
                    ->map(fn (MenuItemModel $child): array => [
                        'label' => $child->label,
                        'url' => $child->url,
                        'opens_in_new_tab' => $child->opens_in_new_tab,
                    ])
                    ->all(),
            ])
            ->all();
    }
}
