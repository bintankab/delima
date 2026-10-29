<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisInisiator;
use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InnovationRequest;
use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\ReviewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class InnovationController extends Controller
{
    /**
     * Statuses that are still awaiting a reviewer decision.
     *
     * @var list<string>
     */
    private const REVIEWABLE_STATUSES = ['submitted', 'under_review', 'revision_needed'];

    /**
     * The full lifecycle of an innovation's status, in order.
     *
     * @var list<string>
     */
    private const STATUSES = ['draft', 'submitted', 'under_review', 'reviewed', 'revision_needed', 'published', 'rejected', 'archived'];

    public function index(Request $request): Response
    {
        $actor = $request->user();
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $trashed = $request->boolean('trashed');
        $status = $request->string('status')->trim()->toString();
        $status = in_array($status, self::STATUSES, true) ? $status : null;
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, [
            'title', 'jenis_inovasi', 'tahapan', 'tahun', 'testing_at', 'implementation_at', 'status', 'origin', 'deleted_at',
        ]);

        $innovations = InnovationModel::query()
            ->when(! $actor->hasRole('super-admin') && $actor->hasRole('admin-opd'), function ($query) use ($actor): void {
                $ownOrganisasiIds = $actor->organisasis()->pluck('organisasis.id');

                $query->whereHas('user.organisasis', fn ($q) => $q->whereIn('organisasis.id', $ownOrganisasiIds));
            })
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->with(['category:id,name', 'media'])
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
                'inisiator_label' => $innovation->inisiator?->label(),
                'category' => $innovation->category?->name,
                'testing_at' => $innovation->testing_at?->toDateString(),
                'implementation_at' => $innovation->implementation_at?->toDateString(),
                'deleted_at' => $innovation->deleted_at?->toDateTimeString(),
                'editable' => $innovation->user_id === $actor->id && $innovation->status !== 'published',
            ]);

        return Inertia::render('Admin/Innovations/Index', [
            'meta' => ['title' => 'Data Inovasi'],
            'innovations' => $innovations,
            'statusOptions' => array_map(
                fn (string $status): array => ['id' => $status, 'name' => $this->statusLabel($status)],
                self::STATUSES,
            ),
            'filters' => [
                'search' => $search,
                'trashed' => $trashed,
                'status' => $status,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $inisiator = JenisInisiator::fromRole($request->user()->getRoleNames()->first() ?? '');

        return Inertia::render('Admin/Innovations/Form', [
            'meta' => ['title' => 'Ajukan Inovasi'],
            'innovation' => null,
            'categories' => $this->categoryOptions(),
            'jenisOptions' => JenisInovasi::options(),
            'tahapanOptions' => TahapanInovasi::options(),
            'inisiatorOptions' => JenisInisiator::options(),
            'inisiator' => $inisiator?->value,
            'inisiatorLocked' => $inisiator !== null,
            'maxYear' => now()->year,
        ]);
    }

    public function store(InnovationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $autoInisiator = JenisInisiator::fromRole($user->getRoleNames()->first() ?? '');
        $inisiator = $autoInisiator ?? JenisInisiator::from($request->validated('inisiator'));

        $validated = $request->safe()->only([
            'category_id',
            'title',
            'jenis_inovasi',
            'tahapan',
            'description',
            'latar_belakang',
            'manfaat',
            'link_video',
            'link_file_pendukung',
            'tahun',
            'testing_at',
            'implementation_at',
        ]);

        $innovation = InnovationModel::query()->create([
            ...$validated,
            'slug' => InnovationModel::uniqueSlug($validated['title']),
            'user_id' => $user->id,
            'inisiator' => $inisiator,
            'status' => 'submitted',
            'origin' => 'submission',
        ]);

        if ($request->hasFile('cover')) {
            $innovation->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        foreach ($this->documents($request) as $document) {
            $innovation->addMedia($document)->toMediaCollection('documents');
        }

        if ($user->hasAnyRole(['super-admin', 'admin-opd'])) {
            return redirect()
                ->route('admin.innovations.show', $innovation)
                ->with('success', 'Inovasi berhasil diajukan.');
        }

        return redirect()
            ->route('home')
            ->with('success', 'Inovasi berhasil diajukan dan akan segera ditinjau.');
    }

    public function show(Request $request, InnovationModel $innovation): Response
    {
        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super-admin');
        $isOwner = $innovation->user_id === $actor->id;
        $canReview = $isSuperAdmin || $actor->hasRole('reviewer');

        return $this->renderShow($innovation, [
            'reviewable' => $canReview && in_array($innovation->status, self::REVIEWABLE_STATUSES, true),
            'canManage' => $isSuperAdmin || ($isOwner && $innovation->status !== 'published'),
            'backRoute' => 'admin.innovations.index',
            'editRoute' => $isSuperAdmin ? 'admin.innovations.edit' : 'admin.innovations.mine.edit',
            'deleteRoute' => $isSuperAdmin ? 'admin.innovations.destroy' : 'admin.innovations.mine.destroy',
            'relatedInnovations' => $this->relatedInnovations($innovation),
        ]);
    }

    public function mine(Request $request): Response
    {
        $user = $request->user();
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, ['title', 'jenis_inovasi', 'tahapan', 'tahun', 'status']);

        $innovations = InnovationModel::query()
            ->where('user_id', $user->id)
            ->with(['category:id,name', 'media'])
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
                'jenis_inovasi_label' => $innovation->jenis_inovasi?->label(),
                'tahapan_label' => $innovation->tahapan->label(),
                'tahun' => $innovation->tahun,
                'status' => $innovation->status,
                'status_label' => $this->statusLabel($innovation->status),
                'category' => $innovation->category?->name,
                'editable' => $innovation->status !== 'published',
            ]);

        return Inertia::render('Admin/Innovations/Mine', [
            'meta' => ['title' => 'Inovasi Saya'],
            'innovations' => $innovations,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function showMine(Request $request, InnovationModel $innovation): Response
    {
        abort_unless($innovation->user_id === $request->user()->id, 403);

        return $this->renderShow($innovation, [
            'reviewable' => false,
            'canManage' => $innovation->status !== 'published',
            'backRoute' => 'admin.innovations.mine',
            'editRoute' => 'admin.innovations.mine.edit',
            'deleteRoute' => 'admin.innovations.mine.destroy',
            'relatedInnovations' => [],
        ]);
    }

    public function editMine(Request $request, InnovationModel $innovation): Response
    {
        $this->authorizeOwnedAndEditable($request, $innovation);

        $innovation->load(['category:id,name']);

        return Inertia::render('Admin/Innovations/Form', [
            'meta' => ['title' => 'Ubah Inovasi'],
            'innovation' => $this->detail($innovation),
            'categories' => $this->categoryOptions(),
            'jenisOptions' => JenisInovasi::options(),
            'tahapanOptions' => TahapanInovasi::options(),
            'inisiatorOptions' => JenisInisiator::options(),
            'inisiator' => $innovation->inisiator?->value,
            'inisiatorLocked' => true,
            'maxYear' => now()->year,
            'updateRoute' => 'admin.innovations.mine.update',
            'showRoute' => 'admin.innovations.mine.show',
        ]);
    }

    public function updateMine(InnovationRequest $request, InnovationModel $innovation): RedirectResponse
    {
        $validated = $request->safe()->only([
            'category_id',
            'title',
            'jenis_inovasi',
            'tahapan',
            'description',
            'latar_belakang',
            'manfaat',
            'link_video',
            'link_file_pendukung',
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
            ->route('admin.innovations.mine.show', $innovation)
            ->with('success', 'Inovasi berhasil diperbarui.');
    }

    public function destroyMine(Request $request, InnovationModel $innovation): RedirectResponse
    {
        $this->authorizeOwnedAndEditable($request, $innovation);

        $innovation->delete();

        $redirectRoute = $request->string('redirect_route')->toString();
        $allowedRedirects = ['admin.innovations.mine', 'admin.innovations.index'];

        if (! in_array($redirectRoute, $allowedRedirects, true)) {
            $redirectRoute = 'admin.innovations.mine';
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Inovasi berhasil dihapus.');
    }

    private function authorizeOwnedAndEditable(Request $request, InnovationModel $innovation): void
    {
        abort_unless($innovation->user_id === $request->user()->id, 403);
        abort_if($innovation->status === 'published', 403, 'Inovasi yang sudah disetujui tidak dapat diubah atau dihapus.');
    }

    /**
     * @param  array{reviewable: bool, canManage: bool, backRoute: string, editRoute: string, deleteRoute: string, relatedInnovations: list<array<string, mixed>>}  $context
     */
    private function renderShow(InnovationModel $innovation, array $context): Response
    {
        $innovation->load(['category:id,name', 'user:id,name', 'reviews.reviewer:id,name']);

        return Inertia::render('Admin/Innovations/Show', [
            'meta' => ['title' => $innovation->title],
            'canManage' => $context['canManage'],
            'backRoute' => $context['backRoute'],
            'editRoute' => $context['editRoute'],
            'deleteRoute' => $context['deleteRoute'],
            'innovation' => [
                ...$this->detail($innovation),
                'views_count' => $innovation->views_count,
                'read_minutes' => $this->readMinutes($innovation->description),
                'reviewable' => $context['reviewable'],
                'reviews' => $innovation->reviews
                    ->sortByDesc('reviewed_at')
                    ->map(fn (ReviewModel $review): array => [
                        'id' => $review->id,
                        'status' => $review->status,
                        'catatan' => $review->catatan,
                        'reviewer' => $review->reviewer?->name,
                        'reviewed_at' => $review->reviewed_at?->toDateTimeString(),
                    ])
                    ->values()
                    ->all(),
            ],
            'relatedInnovations' => $context['relatedInnovations'],
        ]);
    }

    public function approve(Request $request, InnovationModel $innovation): RedirectResponse
    {
        if (! in_array($innovation->status, self::REVIEWABLE_STATUSES, true)) {
            return back()->with('error', 'Inovasi ini tidak dapat ditinjau pada status saat ini.');
        }

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $innovation->update([
            'status' => 'published',
            'assigned_reviewer_id' => $request->user()->id,
        ]);

        $innovation->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'status' => 'approved',
            'catatan' => $validated['catatan'] ?? null,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.innovations.show', $innovation)
            ->with('success', 'Inovasi berhasil disetujui dan diterbitkan.');
    }

    public function reject(Request $request, InnovationModel $innovation): RedirectResponse
    {
        if (! in_array($innovation->status, self::REVIEWABLE_STATUSES, true)) {
            return back()->with('error', 'Inovasi ini tidak dapat ditinjau pada status saat ini.');
        }

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $innovation->update([
            'status' => 'rejected',
            'assigned_reviewer_id' => $request->user()->id,
        ]);

        $innovation->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'status' => 'rejected',
            'catatan' => $validated['catatan'],
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.innovations.show', $innovation)
            ->with('success', 'Inovasi ditolak.');
    }

    public function edit(InnovationModel $innovation): Response
    {
        $innovation->load(['category:id,name']);

        return Inertia::render('Admin/Innovations/Form', [
            'meta' => ['title' => 'Ubah Inovasi'],
            'innovation' => $this->detail($innovation),
            'categories' => $this->categoryOptions(),
            'jenisOptions' => JenisInovasi::options(),
            'tahapanOptions' => TahapanInovasi::options(),
            'inisiatorOptions' => JenisInisiator::options(),
            'inisiator' => $innovation->inisiator?->value,
            'inisiatorLocked' => false,
            'maxYear' => now()->year,
        ]);
    }

    public function update(InnovationRequest $request, InnovationModel $innovation): RedirectResponse
    {
        $validated = $request->safe()->only([
            'inisiator',
            'category_id',
            'title',
            'jenis_inovasi',
            'tahapan',
            'description',
            'latar_belakang',
            'manfaat',
            'link_video',
            'link_file_pendukung',
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
            'link_file_pendukung' => $innovation->link_file_pendukung,
            'tahun' => $innovation->tahun,
            'status' => $innovation->status,
            'origin' => $innovation->origin,
            'testing_at' => $innovation->testing_at?->toDateString(),
            'implementation_at' => $innovation->implementation_at?->toDateString(),
            'inisiator' => $innovation->inisiator?->value,
            'inisiator_label' => $innovation->inisiator?->label(),
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

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draf',
            'submitted' => 'Diajukan',
            'under_review' => 'Ditinjau',
            'reviewed' => 'Direview',
            'revision_needed' => 'Perlu revisi',
            'published' => 'Tayang',
            'rejected' => 'Ditolak',
            'archived' => 'Diarsipkan',
            default => $status,
        };
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
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (InnovationModel $related): array => [
                'id' => $related->id,
                'title' => $related->title,
                'slug' => $related->slug,
                'cover' => $related->getFirstMedia('cover')?->getUrl('thumb'),
                'inisiator_label' => $related->inisiator?->label(),
                'implementation_at' => $related->implementation_at?->toDateString(),
                'read_minutes' => $this->readMinutes($related->description),
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
