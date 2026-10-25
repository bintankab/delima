<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InnovationCategoryRequest;
use App\Models\InnovationCategoryModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InnovationCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);

        $categories = InnovationCategoryModel::query()
            ->with('parent:id,name')
            ->withCount(['children', 'innovations', 'ideas'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->orderByRaw('parent_id is null desc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (InnovationCategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
                'children_count' => $category->children_count,
                'usage_count' => $category->innovations_count + $category->ideas_count,
                'can_delete' => $category->children_count === 0
                    && $category->innovations_count === 0
                    && $category->ideas_count === 0,
            ]);

        return Inertia::render('Admin/InnovationCategories/Index', [
            'meta' => ['title' => 'Kategori Inovasi'],
            'categories' => $categories,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/InnovationCategories/Form', [
            'meta' => ['title' => 'Tambah Kategori Inovasi'],
            'category' => null,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(InnovationCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        InnovationCategoryModel::create([
            'name' => $validated['name'],
            'slug' => InnovationCategoryModel::uniqueSlug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
        ]);

        return redirect()
            ->route('admin.innovation-categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(InnovationCategoryModel $innovationCategory): Response
    {
        return Inertia::render('Admin/InnovationCategories/Form', [
            'meta' => ['title' => 'Ubah Kategori Inovasi'],
            'category' => [
                'id' => $innovationCategory->id,
                'name' => $innovationCategory->name,
                'slug' => $innovationCategory->slug,
                'icon' => $innovationCategory->icon,
                'parent_id' => $innovationCategory->parent_id,
            ],
            'parents' => $this->parentOptions($innovationCategory),
        ]);
    }

    public function update(InnovationCategoryRequest $request, InnovationCategoryModel $innovationCategory): RedirectResponse
    {
        $validated = $request->validated();

        $innovationCategory->update([
            'name' => $validated['name'],
            'slug' => $innovationCategory->name === $validated['name']
                ? $innovationCategory->slug
                : InnovationCategoryModel::uniqueSlug($validated['name'], $innovationCategory->id),
            'parent_id' => $validated['parent_id'] ?? null,
            'icon' => $validated['icon'] ?? null,
        ]);

        return redirect()
            ->route('admin.innovation-categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(InnovationCategoryModel $innovationCategory): RedirectResponse
    {
        $innovationCategory->loadCount(['children', 'innovations', 'ideas']);

        if ($innovationCategory->children_count > 0
            || $innovationCategory->innovations_count > 0
            || $innovationCategory->ideas_count > 0) {
            return back()->with('error', 'Kategori masih memiliki subkategori atau data terkait.');
        }

        $innovationCategory->delete();

        return redirect()
            ->route('admin.innovation-categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function parentOptions(?InnovationCategoryModel $except = null): array
    {
        $excluded = $except === null ? [] : array_merge([$except->id], $except->descendantIds());

        return InnovationCategoryModel::query()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->reject(fn (InnovationCategoryModel $category): bool => in_array($category->id, $excluded, true))
            ->map(fn (InnovationCategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? $category->parent->name.' / '.$category->name : $category->name,
            ])
            ->values()
            ->all();
    }
}
