<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\CategoryModel;
use App\Models\PostModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $status = $request->string('status')->trim()->toString();
        $status = in_array($status, PostRequest::STATUSES, true) ? $status : null;
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, [
            'title', 'status', 'published_at', 'views_count',
        ]);

        $posts = PostModel::query()
            ->with(['user:id,name', 'categories:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';
                $query->where('title', 'like', $escaped);
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->latest(),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PostModel $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'status' => $item->status,
                'status_label' => $this->statusLabel($item->status),
                'published_at' => $item->published_at?->toDateString(),
                'views_count' => $item->views_count,
                'author' => $item->user?->name,
                'categories' => $item->categories->pluck('name')->all(),
            ]);

        return Inertia::render('Admin/Posts/A-Posts-Index', [
            'meta' => ['title' => 'Post'],
            'posts' => $posts,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/Posts/A-Posts-Form', [
            'meta' => ['title' => 'Tulis Post'],
            'post' => null,
            'author' => $request->user()->name,
            'statuses' => $this->statusOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $validated = $request->safe()->except(['cover', 'category_ids']);

        $post = PostModel::query()->create([
            ...$validated,
            'excerpt' => $this->excerpt($validated['excerpt'] ?? null, $validated['content']),
            'slug' => PostModel::uniqueSlug($validated['title']),
            'published_at' => $this->publishedAt($validated['status'], $validated['published_at'] ?? null),
            'user_id' => $request->user()->id,
        ]);

        if ($request->hasFile('cover')) {
            $post->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        $post->categories()->sync($request->validated('category_ids'));

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post berhasil disimpan.');
    }

    public function edit(PostModel $post): Response
    {
        $post->load(['user:id,name', 'categories:id']);
        $cover = $post->getFirstMedia('cover');

        return Inertia::render('Admin/Posts/A-Posts-Form', [
            'meta' => ['title' => 'Ubah Post'],
            'author' => $post->user?->name ?? '—',
            'statuses' => $this->statusOptions(),
            'categories' => $this->categoryOptions(),
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'status' => $post->status,
                'published_at' => $post->published_at?->toDateString(),
                'category_ids' => $post->categories->pluck('id')->all(),
                'cover' => $cover === null ? null : [
                    'url' => $cover->getUrl(),
                    'thumb' => $cover->getUrl('thumb'),
                ],
            ],
        ]);
    }

    public function update(PostRequest $request, PostModel $post): RedirectResponse
    {
        $validated = $request->safe()->except(['cover', 'category_ids']);

        $post->update([
            ...$validated,
            'excerpt' => $this->excerpt($validated['excerpt'] ?? null, $validated['content']),
            'slug' => $post->title === $validated['title']
                ? $post->slug
                : PostModel::uniqueSlug($validated['title'], $post->id),
            'published_at' => $this->publishedAt($validated['status'], $validated['published_at'] ?? null),
        ]);

        if ($request->hasFile('cover')) {
            $post->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        $post->categories()->sync($request->validated('category_ids'));

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post berhasil diperbarui.');
    }

    public function destroy(PostModel $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->route('admin.posts.index')
            ->with('success', 'Post dipindahkan ke sampah.');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (string $status): array => ['id' => $status, 'name' => $this->statusLabel($status)],
            PostRequest::STATUSES,
        );
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'published' => 'Terbit',
            default => $status,
        };
    }

    private function publishedAt(string $status, mixed $publishedAt): mixed
    {
        if ($status === 'published' && ($publishedAt === null || $publishedAt === '')) {
            return now();
        }

        return $publishedAt;
    }

    /**
     * @return list<array{id: int, name: string, depth: int}>
     */
    private function categoryOptions(): array
    {
        $grouped = [];
        foreach (CategoryModel::query()->orderBy('name')->get(['id', 'parent_id', 'name']) as $category) {
            $grouped[$category->parent_id ?? 0][] = $category;
        }

        $flat = [];
        $walk = function (int $parentId, int $depth) use (&$walk, &$flat, $grouped): void {
            foreach ($grouped[$parentId] ?? [] as $category) {
                $flat[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'depth' => $depth,
                ];
                $walk($category->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $flat;
    }

    private function excerpt(?string $excerpt, string $content): ?string
    {
        if ($excerpt !== null && trim($excerpt) !== '') {
            return trim($excerpt);
        }

        $text = html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text === '' ? null : Str::words($text, 30, '');
    }
}
