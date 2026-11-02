<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\CategoryModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, ['name', 'children_count', 'posts_count']);

        $categories = CategoryModel::query()
            ->with('parent:id,name')
            ->withCount(['children', 'posts'])
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
            ->through(fn (CategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
                'children_count' => $category->children_count,
                'posts_count' => $category->posts_count,
                'can_delete' => $category->children_count === 0,
            ]);

        return Inertia::render('Admin/PostCategories/A-PostCategories-Index', [
            'meta' => ['title' => 'Kategori Post'],
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
        return Inertia::render('Admin/PostCategories/A-PostCategories-Form', [
            'meta' => ['title' => 'Tambah Kategori Post'],
            'category' => null,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        CategoryModel::query()->create([
            'name' => $validated['name'],
            'slug' => CategoryModel::uniqueSlug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.post-categories.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(CategoryModel $category): Response
    {
        return Inertia::render('Admin/PostCategories/A-PostCategories-Form', [
            'meta' => ['title' => 'Ubah Kategori Post'],
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
            ],
            'parents' => $this->parentOptions($category),
        ]);
    }

    public function update(CategoryRequest $request, CategoryModel $category): RedirectResponse
    {
        $validated = $request->validated();

        $category->update([
            'name' => $validated['name'],
            'slug' => $category->name === $validated['name']
                ? $category->slug
                : CategoryModel::uniqueSlug($validated['name'], $category->id),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.post-categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(CategoryModel $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Kategori masih memiliki subkategori.');
        }

        $category->delete();

        return redirect()
            ->route('admin.post-categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function parentOptions(?CategoryModel $except = null): array
    {
        $excluded = $except === null ? [] : array_merge([$except->id], $except->descendantIds());

        return CategoryModel::query()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->reject(fn (CategoryModel $category): bool => in_array($category->id, $excluded, true))
            ->map(fn (CategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? $category->parent->name.' / '.$category->name : $category->name,
            ])
            ->values()
            ->all();
    }
}
