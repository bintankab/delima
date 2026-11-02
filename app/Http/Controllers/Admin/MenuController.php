<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MenuItemRequest;
use App\Models\MenuItemModel;
use App\Models\MenuModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        $menu = $this->headerMenu();
        $items = $menu->items()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Menus/A-Menus-Index', [
            'meta' => ['title' => 'Menu'],
            'menu' => [
                'name' => $menu->name,
                'location' => $menu->location,
            ],
            'items' => $this->rows($items),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Menus/A-Menus-Form', [
            'meta' => ['title' => 'Tambah Menu'],
            'item' => null,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(MenuItemRequest $request): RedirectResponse
    {
        $menu = $this->headerMenu();
        $parentId = $this->parentId($request);

        MenuItemModel::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parentId,
            'label' => $request->validated('label'),
            'url' => $request->validated('url'),
            'opens_in_new_tab' => $request->boolean('opens_in_new_tab'),
            'sort_order' => $this->nextSortOrder($menu->id, $parentId),
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(MenuItemModel $menuItem): Response
    {
        $this->guardItem($menuItem);

        return Inertia::render('Admin/Menus/A-Menus-Form', [
            'meta' => ['title' => 'Ubah Menu'],
            'item' => [
                'id' => $menuItem->id,
                'label' => $menuItem->label,
                'url' => $menuItem->url,
                'parent_id' => $menuItem->parent_id,
                'opens_in_new_tab' => $menuItem->opens_in_new_tab,
                'has_children' => $menuItem->children()->exists(),
            ],
            'parents' => $this->parentOptions($menuItem),
        ]);
    }

    public function update(MenuItemRequest $request, MenuItemModel $menuItem): RedirectResponse
    {
        $this->guardItem($menuItem);

        $parentId = $this->parentId($request);
        $attributes = [
            'parent_id' => $parentId,
            'label' => $request->validated('label'),
            'url' => $request->validated('url'),
            'opens_in_new_tab' => $request->boolean('opens_in_new_tab'),
        ];

        if ($menuItem->parent_id !== $parentId) {
            $attributes['sort_order'] = $this->nextSortOrder($menuItem->menu_id, $parentId);
        }

        $menuItem->update($attributes);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function destroy(MenuItemModel $menuItem): RedirectResponse
    {
        $this->guardItem($menuItem);

        $next = $this->nextSortOrder($menuItem->menu_id, null);
        $children = $menuItem->children()->get();

        foreach ($children as $child) {
            $child->update([
                'parent_id' => null,
                'sort_order' => $next,
            ]);
            $next++;
        }

        $menuItem->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil dihapus.');
    }

    public function moveUp(MenuItemModel $menuItem): RedirectResponse
    {
        return $this->move($menuItem, -1);
    }

    public function moveDown(MenuItemModel $menuItem): RedirectResponse
    {
        return $this->move($menuItem, 1);
    }

    private function headerMenu(): MenuModel
    {
        return MenuModel::ensureHeader();
    }

    private function guardItem(MenuItemModel $menuItem): void
    {
        $menu = MenuModel::query()->where('location', 'header')->first();

        abort_unless($menu !== null && $menuItem->menu_id === $menu->id, 404);
    }

    private function parentId(MenuItemRequest $request): ?int
    {
        $parentId = $request->validated('parent_id');

        return $parentId === null ? null : (int) $parentId;
    }

    private function nextSortOrder(int $menuId, ?int $parentId): int
    {
        $max = MenuItemModel::query()
            ->where('menu_id', $menuId)
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return ($max === null ? -1 : (int) $max) + 1;
    }

    private function move(MenuItemModel $menuItem, int $direction): RedirectResponse
    {
        $this->guardItem($menuItem);

        $siblings = MenuItemModel::query()
            ->where('menu_id', $menuItem->menu_id)
            ->where('parent_id', $menuItem->parent_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $siblings->search(fn (MenuItemModel $item): bool => $item->id === $menuItem->id);

        if ($index === false) {
            return back();
        }

        $target = $index + $direction;

        if ($target < 0 || $target >= $siblings->count()) {
            return back();
        }

        $ids = $siblings->pluck('id')->all();
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        foreach ($ids as $position => $id) {
            MenuItemModel::query()->whereKey($id)->update(['sort_order' => $position]);
        }

        return back();
    }

    /**
     * @param  Collection<int, MenuItemModel>  $items
     * @return list<array<string, mixed>>
     */
    private function rows(Collection $items): array
    {
        return $items->values()->map(function (MenuItemModel $item, int $index) use ($items): array {
            return [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $item->url,
                'opens_in_new_tab' => $item->opens_in_new_tab,
                'can_move_up' => $index > 0,
                'can_move_down' => $index < $items->count() - 1,
                'children' => $this->rows($item->children),
            ];
        })->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function parentOptions(?MenuItemModel $except = null): array
    {
        return $this->headerMenu()->items()
            ->whereNull('parent_id')
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'label'])
            ->map(fn (MenuItemModel $item): array => [
                'id' => $item->id,
                'label' => $item->label,
            ])
            ->all();
    }
}
