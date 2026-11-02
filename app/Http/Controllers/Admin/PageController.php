<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PageLayout;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\PageModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PageController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $status = $request->string('status')->trim()->toString();
        $status = in_array($status, PageRequest::STATUSES, true) ? $status : null;
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, [
            'title', 'layout', 'status', 'published_at', 'views_count',
        ]);

        $pages = PageModel::query()
            ->with('user:id,name')
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
            ->through(fn (PageModel $item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'layout' => $item->layout,
                'layout_label' => PageLayout::tryFrom($item->layout)?->label() ?? $item->layout,
                'status' => $item->status,
                'status_label' => $this->statusLabel($item->status),
                'published_at' => $item->published_at?->toDateString(),
                'views_count' => $item->views_count,
                'author' => $item->user?->name,
            ]);

        return Inertia::render('Admin/Pages/A-Pages-Index', [
            'meta' => ['title' => 'Page'],
            'pages' => $pages,
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
        return Inertia::render('Admin/Pages/A-Pages-Form', [
            'meta' => ['title' => 'Tulis Page'],
            'page' => null,
            'author' => $request->user()->name,
            'statuses' => $this->statusOptions(),
            'layouts' => PageLayout::options(),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $validated = $request->safe()->except(['cards', 'slides', 'new_slides', 'new_slide_captions']);
        $layout = PageLayout::from($validated['layout']);

        $page = PageModel::query()->create([
            ...$validated,
            'excerpt' => $this->excerpt($validated['excerpt'] ?? null, $validated['content']),
            'slug' => PageModel::uniqueSlug($validated['title']),
            'published_at' => $this->publishedAt($validated['status'], $validated['published_at'] ?? null),
            'fields' => null,
            'user_id' => $request->user()->id,
        ]);

        $this->syncLayoutContent($request, $page, $layout);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page berhasil disimpan.');
    }

    public function edit(PageModel $page): Response
    {
        $page->load('user:id,name');

        return Inertia::render('Admin/Pages/A-Pages-Form', [
            'meta' => ['title' => 'Ubah Page'],
            'author' => $page->user?->name ?? '—',
            'statuses' => $this->statusOptions(),
            'layouts' => PageLayout::options(),
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'excerpt' => $page->excerpt,
                'content' => $page->content,
                'layout' => $page->layout,
                'status' => $page->status,
                'published_at' => $page->published_at?->toDateString(),
                'cards' => $this->cardPayload($page),
                'slides' => $this->slidePayload($page),
            ],
        ]);
    }

    public function update(PageRequest $request, PageModel $page): RedirectResponse
    {
        $validated = $request->safe()->except(['cards', 'slides', 'new_slides', 'new_slide_captions']);
        $layout = PageLayout::from($validated['layout']);

        $page->update([
            ...$validated,
            'excerpt' => $this->excerpt($validated['excerpt'] ?? null, $validated['content']),
            'slug' => $page->title === $validated['title']
                ? $page->slug
                : PageModel::uniqueSlug($validated['title'], $page->id),
            'published_at' => $this->publishedAt($validated['status'], $validated['published_at'] ?? null),
        ]);

        $this->syncLayoutContent($request, $page, $layout);

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page berhasil diperbarui.');
    }

    public function destroy(PageModel $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page dipindahkan ke sampah.');
    }

    private function syncLayoutContent(PageRequest $request, PageModel $page, PageLayout $layout): void
    {
        if ($layout->hasCards()) {
            $page->update([
                'fields' => ['cards' => $this->syncCards($request, $page)],
            ]);
        }

        if ($layout->hasSlides()) {
            $this->syncSlides($request, $page);
        }
    }

    /**
     * @return list<array{title: string, body: string, image_id: int|null}>
     */
    private function syncCards(PageRequest $request, PageModel $page): array
    {
        $ownedImages = $page->getMedia('images')->keyBy('id');
        $stored = [];
        $keptIds = [];

        foreach ($request->validated('cards') as $index => $card) {
            $imageId = isset($card['image_id']) ? (int) $card['image_id'] : null;
            $upload = $request->file('cards.'.$index.'.image');

            if ($upload instanceof UploadedFile) {
                $media = $page->addMedia($upload)->toMediaCollection('images');
                $imageId = $media->id;
                $ownedImages->put($media->id, $media);
            } elseif ($imageId === null || ! $ownedImages->has($imageId)) {
                $imageId = null;
            }

            if ($imageId !== null) {
                $keptIds[] = $imageId;
            }

            $stored[] = [
                'title' => $card['title'],
                'body' => trim((string) ($card['body'] ?? '')),
                'image_id' => $imageId,
            ];
        }

        $ownedImages->each(function (Media $media) use ($keptIds): void {
            if (! in_array($media->id, $keptIds, true)) {
                $media->delete();
            }
        });

        return $stored;
    }

    private function syncSlides(PageRequest $request, PageModel $page): void
    {
        $existing = $page->getMedia('slides')->keyBy('id');
        $orderedIds = [];

        foreach ($request->validated('slides') as $slide) {
            $media = $existing->get((int) $slide['id']);
            if ($media === null) {
                continue;
            }

            $caption = trim((string) ($slide['caption'] ?? ''));
            $media->setCustomProperty('caption', $caption === '' ? null : $caption);
            $media->save();
            $orderedIds[] = $media->id;
        }

        $existing->each(function (Media $media) use ($orderedIds): void {
            if (! in_array($media->id, $orderedIds, true)) {
                $media->delete();
            }
        });

        $captions = $request->validated('new_slide_captions');
        foreach ($request->file('new_slides', []) as $index => $upload) {
            if (! $upload instanceof UploadedFile) {
                continue;
            }

            $caption = trim((string) ($captions[$index] ?? ''));
            $media = $page->addMedia($upload)
                ->withCustomProperties(['caption' => $caption === '' ? null : $caption])
                ->toMediaCollection('slides');
            $orderedIds[] = $media->id;
        }

        if ($orderedIds !== []) {
            Media::setNewOrder($orderedIds);
        }
    }

    /**
     * @return list<array{title: string, body: string, image_id: int|null, image_url: string|null}>
     */
    private function cardPayload(PageModel $page): array
    {
        $images = $page->getMedia('images')->keyBy('id');
        $cards = [];

        foreach ($page->fields['cards'] ?? [] as $card) {
            $media = $images->get($card['image_id'] ?? 0);
            $cards[] = [
                'title' => $card['title'] ?? '',
                'body' => $card['body'] ?? '',
                'image_id' => $media?->id,
                'image_url' => $media?->getUrl(),
            ];
        }

        return $cards;
    }

    /**
     * @return list<array{id: int, caption: string, url: string}>
     */
    private function slidePayload(PageModel $page): array
    {
        return $page->getMedia('slides')
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'caption' => (string) $media->getCustomProperty('caption', ''),
                'url' => $media->getUrl(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (string $status): array => ['id' => $status, 'name' => $this->statusLabel($status)],
            PageRequest::STATUSES,
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
