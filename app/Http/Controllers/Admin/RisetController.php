<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RisetArchiveRequest;
use App\Http\Requests\Admin\RisetRequest;
use App\Models\OrganisasiModel;
use App\Models\ReviewModel;
use App\Models\RisetCategoryModel;
use App\Models\RisetModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class RisetController extends Controller
{
    /**
     * Statuses that are still awaiting a reviewer decision.
     *
     * @var list<string>
     */
    private const REVIEWABLE_STATUSES = ['submitted', 'under_review', 'revision_needed'];

    /**
     * The full lifecycle of a riset's status, in order.
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
            'title', 'peneliti', 'tahun', 'published_at', 'status', 'origin', 'deleted_at',
        ]);

        $risets = RisetModel::query()
            ->when(! $actor->hasRole('super-admin') && $actor->hasRole('admin-opd'), function ($query) use ($actor): void {
                $ownOrganisasiIds = $actor->organisasis()->pluck('organisasis.id');

                $query->whereIn('organisasi_id', $ownOrganisasiIds);
            })
            ->when($trashed, fn ($query) => $query->onlyTrashed())
            ->with(['organisasi:id,name', 'category:id,name', 'media'])
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($escaped): void {
                    $query->where('title', 'like', $escaped)
                        ->orWhere('peneliti', 'like', $escaped);
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->latest(),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (RisetModel $riset): array => [
                'id' => $riset->id,
                'title' => $riset->title,
                'cover' => $riset->getFirstMedia('cover')?->getUrl('thumb'),
                'views_count' => $riset->views_count,
                'peneliti' => $riset->peneliti,
                'tahun' => $riset->tahun,
                'status' => $riset->status,
                'origin' => $riset->origin,
                'organisasi' => $riset->organisasi?->name,
                'category' => $riset->category?->name,
                'published_at' => $riset->published_at?->toDateString(),
                'deleted_at' => $riset->deleted_at?->toDateTimeString(),
                'editable' => $riset->user_id === $actor->id && $riset->status !== 'published',
            ]);

        return Inertia::render('Admin/Risets/Index', [
            'meta' => ['title' => 'Daftar Riset'],
            'risets' => $risets,
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
        $organisasi = $request->user()->organisasis()->first();

        return Inertia::render('Admin/Risets/ArchiveForm', [
            'meta' => ['title' => 'Ajukan Riset'],
            'organisasis' => $this->organisasiOptions(),
            'categories' => $this->categoryOptions(),
            'maxYear' => now()->year,
            'heading' => 'Ajukan Riset',
            'description' => 'Riset yang Anda ajukan akan ditinjau oleh reviewer sebelum tayang di halaman publik.',
            'submitRoute' => 'admin.risets.store',
            'submitLabel' => 'Ajukan',
            'publishedAtHint' => 'Isi sesuai tanggal riset ini sebenarnya selesai atau diterbitkan.',
            'organisasiLocked' => true,
            'organisasiId' => $organisasi?->id,
            'organisasiName' => $organisasi?->name,
        ]);
    }

    public function store(RisetArchiveRequest $request): RedirectResponse
    {
        $user = $request->user();
        $organisasi = $user->organisasis()->first();

        abort_unless($organisasi !== null, 403, 'Akun Anda belum terhubung ke OPD manapun.');

        $validated = $request->safe()->only([
            'category_id',
            'title',
            'peneliti',
            'abstrak',
            'latar_belakang',
            'metodologi',
            'tahun',
            'published_at',
        ]);

        $riset = RisetModel::query()->create([
            ...$validated,
            'organisasi_id' => $organisasi->id,
            'slug' => RisetModel::uniqueSlug($validated['title']),
            'user_id' => $user->id,
            'status' => 'submitted',
            'origin' => 'submission',
        ]);

        if ($request->hasFile('cover')) {
            $riset->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        foreach ($this->documents($request) as $document) {
            $riset->addMedia($document)->toMediaCollection('documents');
        }

        return redirect()
            ->route('admin.risets.show', $riset)
            ->with('success', 'Riset berhasil diajukan dan akan segera ditinjau.');
    }

    public function show(Request $request, RisetModel $riset): Response
    {
        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super-admin');
        $isOwner = $riset->user_id === $actor->id;
        $canReview = $isSuperAdmin || $actor->hasRole('reviewer');

        $riset->load(['organisasi:id,name', 'category:id,name', 'user:id,name', 'reviews.reviewer:id,name']);

        return Inertia::render('Admin/Risets/Show', [
            'meta' => ['title' => $riset->title],
            'canManage' => $isSuperAdmin || ($isOwner && $riset->status !== 'published'),
            'riset' => [
                ...$this->detail($riset),
                'views_count' => $riset->views_count,
                'read_minutes' => $this->readMinutes($riset->abstrak),
                'reviewable' => $canReview && in_array($riset->status, self::REVIEWABLE_STATUSES, true),
                'reviews' => $riset->reviews
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
            'relatedRisets' => $this->relatedRisets($riset),
        ]);
    }

    public function edit(Request $request, RisetModel $riset): Response
    {
        $this->authorizeManageable($request, $riset);

        $riset->load(['organisasi:id,name', 'category:id,name']);

        return Inertia::render('Admin/Risets/Form', [
            'meta' => ['title' => 'Ubah Riset'],
            'riset' => $this->detail($riset),
            'organisasis' => $this->organisasiOptions(),
            'categories' => $this->categoryOptions(),
            'maxYear' => now()->year,
        ]);
    }

    public function update(RisetRequest $request, RisetModel $riset): RedirectResponse
    {
        $validated = $request->safe()->only([
            'organisasi_id',
            'category_id',
            'title',
            'peneliti',
            'abstrak',
            'latar_belakang',
            'metodologi',
            'tahun',
            'published_at',
        ]);

        if (! $request->user()->hasRole('super-admin')) {
            $validated['organisasi_id'] = $riset->organisasi_id;
        }

        $riset->update([
            ...$validated,
            'slug' => $riset->title === $validated['title']
                ? $riset->slug
                : RisetModel::uniqueSlug($validated['title'], $riset->id),
        ]);

        if ($request->hasFile('cover')) {
            $riset->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        $removeIds = $request->input('remove_document_ids', []);
        if (is_array($removeIds) && $removeIds !== []) {
            $riset->media()
                ->where('collection_name', 'documents')
                ->whereIn('id', $removeIds)
                ->get()
                ->each(fn ($media) => $media->delete());
        }

        foreach ($this->documents($request) as $document) {
            $riset->addMedia($document)->toMediaCollection('documents');
        }

        return redirect()
            ->route('admin.risets.show', $riset)
            ->with('success', 'Riset berhasil diperbarui.');
    }

    public function destroy(Request $request, RisetModel $riset): RedirectResponse
    {
        $this->authorizeManageable($request, $riset);

        $riset->delete();

        return redirect()
            ->route('admin.risets.index')
            ->with('success', 'Riset berhasil dihapus.');
    }

    public function forceDestroy(RisetModel $riset): RedirectResponse
    {
        if (! $riset->trashed()) {
            return back()->with('error', 'Riset harus dihapus (ke sampah) terlebih dahulu sebelum dihapus permanen.');
        }

        $riset->forceDelete();

        return redirect()
            ->route('admin.risets.index', ['trashed' => true])
            ->with('success', 'Riset beserta berkasnya berhasil dihapus permanen.');
    }

    public function approve(Request $request, RisetModel $riset): RedirectResponse
    {
        if (! in_array($riset->status, self::REVIEWABLE_STATUSES, true)) {
            return back()->with('error', 'Riset ini tidak dapat ditinjau pada status saat ini.');
        }

        $validated = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $riset->update([
            'status' => 'published',
            'assigned_reviewer_id' => $request->user()->id,
        ]);

        $riset->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'status' => 'approved',
            'catatan' => $validated['catatan'] ?? null,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.risets.show', $riset)
            ->with('success', 'Riset berhasil disetujui dan diterbitkan.');
    }

    public function reject(Request $request, RisetModel $riset): RedirectResponse
    {
        if (! in_array($riset->status, self::REVIEWABLE_STATUSES, true)) {
            return back()->with('error', 'Riset ini tidak dapat ditinjau pada status saat ini.');
        }

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $riset->update([
            'status' => 'rejected',
            'assigned_reviewer_id' => $request->user()->id,
        ]);

        $riset->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'status' => 'rejected',
            'catatan' => $validated['catatan'],
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('admin.risets.show', $riset)
            ->with('success', 'Riset ditolak.');
    }

    private function authorizeManageable(Request $request, RisetModel $riset): void
    {
        if ($request->user()->hasRole('super-admin')) {
            return;
        }

        abort_unless($riset->user_id === $request->user()->id, 403);
        abort_if($riset->status === 'published', 403, 'Riset yang sudah disetujui tidak dapat diubah atau dihapus.');
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
     * @return array<string, mixed>
     */
    private function detail(RisetModel $riset): array
    {
        $cover = $riset->getFirstMedia('cover');

        return [
            'id' => $riset->id,
            'title' => $riset->title,
            'slug' => $riset->slug,
            'peneliti' => $riset->peneliti,
            'abstrak' => $riset->abstrak,
            'latar_belakang' => $riset->latar_belakang,
            'metodologi' => $riset->metodologi,
            'tahun' => $riset->tahun,
            'status' => $riset->status,
            'origin' => $riset->origin,
            'published_at' => $riset->published_at?->toDateString(),
            'organisasi_id' => $riset->organisasi_id,
            'organisasi' => $riset->organisasi?->name,
            'category_id' => $riset->category_id,
            'category' => $riset->category?->name,
            'user' => $riset->relationLoaded('user') ? $riset->user?->name : null,
            'cover' => $cover === null ? null : [
                'id' => $cover->id,
                'url' => $cover->getUrl(),
                'thumb' => $cover->getUrl('thumb'),
            ],
            'documents' => $riset->getMedia('documents')->map(fn ($media): array => [
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
    private function relatedRisets(RisetModel $riset): array
    {
        return RisetModel::query()
            ->whereKeyNot($riset->id)
            ->when(
                $riset->category_id,
                fn ($query) => $query->where('category_id', $riset->category_id),
            )
            ->with('organisasi:id,name')
            ->latest()
            ->take(2)
            ->get()
            ->map(fn (RisetModel $related): array => [
                'id' => $related->id,
                'title' => $related->title,
                'slug' => $related->slug,
                'cover' => $related->getFirstMedia('cover')?->getUrl('thumb'),
                'organisasi' => $related->organisasi?->name,
                'published_at' => $related->published_at?->toDateString(),
                'read_minutes' => $this->readMinutes($related->abstrak),
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
        return RisetCategoryModel::query()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name'])
            ->map(fn (RisetCategoryModel $category): array => [
                'id' => $category->id,
                'name' => $category->parent ? $category->parent->name.' / '.$category->name : $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<UploadedFile>
     */
    private function documents(RisetRequest|RisetArchiveRequest $request): array
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
