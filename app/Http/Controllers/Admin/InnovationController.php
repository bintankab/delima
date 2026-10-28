<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InnovationRequest;
use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class InnovationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $trashed = $request->boolean('trashed');
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, [
            'title', 'jenis_inovasi', 'tahapan', 'tahun', 'testing_at', 'implementation_at', 'status', 'origin', 'deleted_at',
        ]);

        $innovations = InnovationModel::query()
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->with(['organisasi:id,name', 'category:id,name', 'media'])
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';

                $query->where('title', 'like', $escaped);
            })
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->latest(),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (InnovationModel $innovation): array => [
                'id' => $innovation->id,
                'title' => $innovation->title,
                'cover' => $innovation->getFirstMedia('cover')?->getUrl('thumb'),
                'views_count' => $innovation->views_count,
                'jenis_inovasi_label' => $innovation->jenis_inovasi?->label(),
                'tahapan_label' => $innovation->tahapan->label(),
                'tahun' => $innovation->tahun,
                'status' => $innovation->status,
                'origin' => $innovation->origin,
                'organisasi' => $innovation->organisasi?->name,
                'category' => $innovation->category?->name,
                'testing_at' => $innovation->testing_at?->toDateString(),
                'implementation_at' => $innovation->implementation_at?->toDateString(),
                'deleted_at' => $innovation->deleted_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Innovations/Index', [
            'meta' => ['title' => 'Data Inovasi'],
            'innovations' => $innovations,
            'filters' => [
                'search' => $search,
                'trashed' => $trashed,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function show(InnovationModel $innovation): Response
    {
        $innovation->load(['organisasi:id,name', 'category:id,name', 'user:id,name']);

        return Inertia::render('Admin/Innovations/Show', [
            'meta' => ['title' => $innovation->title],
            'innovation' => [
                ...$this->detail($innovation),
                'views_count' => $innovation->views_count,
                'read_minutes' => $this->readMinutes($innovation->description),
            ],
            'relatedInnovations' => $this->relatedInnovations($innovation),
        ]);
    }

    public function edit(InnovationModel $innovation): Response
    {
        $innovation->load(['organisasi:id,name', 'category:id,name']);

        return Inertia::render('Admin/Innovations/Form', [
            'meta' => ['title' => 'Ubah Inovasi'],
            'innovation' => $this->detail($innovation),
            'organisasis' => $this->organisasiOptions(),
            'categories' => $this->categoryOptions(),
            'jenisOptions' => JenisInovasi::options(),
            'tahapanOptions' => TahapanInovasi::options(),
            'maxYear' => now()->year,
        ]);
    }

    public function update(InnovationRequest $request, InnovationModel $innovation): RedirectResponse
    {
        $validated = $request->safe()->only([
            'organisasi_id',
            'category_id',
            'title',
            'jenis_inovasi',
            'tahapan',
            'description',
            'latar_belakang',
            'manfaat',
            'link_video',
            'tahun',
            'testing_at',
            'implementation_at',
        ]);

        $innovation->update([
            ...$validated,
            'slug' => $innovation->title === $validated['title']
                ? $innovation->slug
                : InnovationModel::uniqueSlug($validated['title'], $innovation->id),
        ]);

        if ($request->hasFile('cover')) {
            $innovation->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        $removeIds = $request->input('remove_document_ids', []);
        if (is_array($removeIds) && $removeIds !== []) {
            $innovation->media()
                ->where('collection_name', 'documents')
                ->whereIn('id', $removeIds)
                ->get()
                ->each(fn ($media) => $media->delete());
        }

        foreach ($this->documents($request) as $document) {
            $innovation->addMedia($document)->toMediaCollection('documents');
        }

        return redirect()
            ->route('admin.innovations.show', $innovation)
            ->with('success', 'Inovasi berhasil diperbarui.');
    }

    public function destroy(InnovationModel $innovation): RedirectResponse
    {
        $innovation->delete();

        return redirect()
            ->route('admin.innovations.index')
            ->with('success', 'Inovasi berhasil dihapus.');
    }

    public function forceDestroy(InnovationModel $innovation): RedirectResponse
    {
        if (! $innovation->trashed()) {
            return back()->with('error', 'Inovasi harus dihapus (ke sampah) terlebih dahulu sebelum dihapus permanen.');
        }

        $innovation->forceDelete();

        return redirect()
            ->route('admin.innovations.index', ['trashed' => true])
            ->with('success', 'Inovasi beserta berkasnya berhasil dihapus permanen.');
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(InnovationModel $innovation): array
    {
        $cover = $innovation->getFirstMedia('cover');

        return [
            'id' => $innovation->id,
            'title' => $innovation->title,
            'slug' => $innovation->slug,
            'jenis_inovasi' => $innovation->jenis_inovasi,
            'jenis_inovasi_label' => $innovation->jenis_inovasi?->label(),
            'tahapan' => $innovation->tahapan,
            'tahapan_label' => $innovation->tahapan->label(),
            'description' => $innovation->description,
            'latar_belakang' => $innovation->latar_belakang,
            'manfaat' => $innovation->manfaat,
            'link_video' => $innovation->link_video,
            'tahun' => $innovation->tahun,
            'status' => $innovation->status,
            'origin' => $innovation->origin,
            'testing_at' => $innovation->testing_at?->toDateString(),
            'implementation_at' => $innovation->implementation_at?->toDateString(),
            'organisasi_id' => $innovation->organisasi_id,
            'organisasi' => $innovation->organisasi?->name,
            'category_id' => $innovation->category_id,
            'category' => $innovation->category?->name,
            'user' => $innovation->relationLoaded('user') ? $innovation->user?->name : null,
            'cover' => $cover === null ? null : [
                'id' => $cover->id,
                'url' => $cover->getUrl(),
                'thumb' => $cover->getUrl('thumb'),
            ],
            'documents' => $innovation->getMedia('documents')->map(fn ($media): array => [
                'id' => $media->id,
                'name' => $media->file_name,
                'size' => $media->human_readable_size,
                'url' => $media->getUrl(),
            ])->values()->all(),
        ];
    }

    /**
     * Estimate reading time (minutes) for Quill-authored HTML content.
     */
    private function readMinutes(?string $html): int
    {
        $words = str_word_count(strip_tags((string) $html));

        return max(1, (int) ceil($words / 200));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function relatedInnovations(InnovationModel $innovation): array
    {
        return InnovationModel::query()
            ->whereKeyNot($innovation->id)
            ->when(
                $innovation->category_id,
                fn ($query) => $query->where('category_id', $innovation->category_id),
            )
            ->with('organisasi:id,name')
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (InnovationModel $related): array => [
                'id' => $related->id,
                'title' => $related->title,
                'slug' => $related->slug,
                'cover' => $related->getFirstMedia('cover')?->getUrl('thumb'),
                'organisasi' => $related->organisasi?->name,
                'implementation_at' => $related->implementation_at?->toDateString(),
                'read_minutes' => $this->readMinutes($related->description),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function organisasiOptions(): array
    {
        return OrganisasiModel::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (OrganisasiModel $organisasi): array => [
                'id' => $organisasi->id,
                'name' => $organisasi->name,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function categoryOptions(): array
    {
        return InnovationCategoryModel::query()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->map(fn (InnovationCategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? $category->parent->name.' / '.$category->name : $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<UploadedFile>
     */
    private function documents(InnovationRequest $request): array
    {
        $documents = $request->file('documents', []);

        if ($documents instanceof UploadedFile) {
            return [$documents];
        }

        return array_values(array_filter(
            is_array($documents) ? $documents : [],
            fn (mixed $document): bool => $document instanceof UploadedFile,
        ));
    }
}
