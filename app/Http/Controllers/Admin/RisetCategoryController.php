<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RisetCategoryRequest;
use App\Models\RisetCategoryModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RisetCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, ['name', 'icon', 'children_count', 'risets_count']);

        $categories = RisetCategoryModel::query()
            ->with('parent:id,name')
            ->withCount(['children', 'risets'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->orderByRaw('parent_id is null desc')->orderBy('name'),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (RisetCategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
                'children_count' => $category->children_count,
                'usage_count' => $category->risets_count,
                'can_delete' => $category->children_count === 0 && $category->risets_count === 0,
            ]);

        return Inertia::render('Admin/RisetCategories/Index', [
            'meta' => ['title' => 'Kategori Riset'],
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/RisetCategories/Form', [
            'meta' => ['title' => 'Tambah Kategori Riset'],
            'category' => null,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(RisetCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        RisetCategoryModel::create([
            'name' => $validated['name'],
            'slug' => RisetCategoryModel::uniqueSlug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
        ]);

        return redirect()
            ->route('admin.riset-categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(RisetCategoryModel $risetCategory): Response
    {
        return Inertia::render('Admin/RisetCategories/Form', [
            'meta' => ['title' => 'Ubah Kategori Riset'],
            'category' => [
                'id' => $risetCategory->id,
                'name' => $risetCategory->name,
                'slug' => $risetCategory->slug,
                'icon' => $risetCategory->icon,
                'parent_id' => $risetCategory->parent_id,
            ],
            'parents' => $this->parentOptions($risetCategory),
        ]);
    }

    public function update(RisetCategoryRequest $request, RisetCategoryModel $risetCategory): RedirectResponse
    {
        $validated = $request->validated();

        $risetCategory->update([
            'name' => $validated['name'],
            'slug' => $risetCategory->name === $validated['name']
                ? $risetCategory->slug
                : RisetCategoryModel::uniqueSlug($validated['name'], $risetCategory->id),
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
        ]);

        return redirect()
            ->route('admin.riset-categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(RisetCategoryModel $risetCategory): RedirectResponse
    {
        $risetCategory->loadCount(['children', 'risets']);

        if ($risetCategory->children_count > 0 || $risetCategory->risets_count > 0) {
            return back()->with('error', 'Kategori masih memiliki subkategori atau data terkait.');
        }

        $risetCategory->delete();

        return redirect()
            ->route('admin.riset-categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function parentOptions(?RisetCategoryModel $except = null): array
    {
        $excluded = $except === null ? [] : array_merge([$except->id], $except->descendantIds());

        return RisetCategoryModel::query()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->reject(fn (RisetCategoryModel $category): bool => in_array($category->id, $excluded, true))
            ->map(fn (RisetCategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? $category->parent->name.' / '.$category->name : $category->name,
            ])
            ->values()
            ->all();
    }
}
