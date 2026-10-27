<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LibraryDocumentRequest;
use App\Models\LibraryDocumentModel;
use App\Models\OrganisasiModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, [
            'title', 'type', 'tahun_terbit', 'tanggal_terbit', 'is_public', 'downloads_count',
        ]);

        $documents = LibraryDocumentModel::query()
            ->when(
                ! $isSuperAdmin,
                fn ($query) => $query->whereIn('organisasi_id', $user->organisasis()->pluck('organisasis.id')),
            )
            ->with('organisasi:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($escaped): void {
                    $query->where('title', 'like', $escaped)
                        ->orWhere('nomor_dokumen', 'like', $escaped);
                });
            })
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->latest(),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (LibraryDocumentModel $document): array => [
                'id' => $document->id,
                'title' => $document->title,
                'type' => $document->type,
                'organisasi' => $document->organisasi?->name,
                'tahun_terbit' => $document->tahun_terbit,
                'is_public' => $document->is_public,
                'downloads_count' => $document->downloads_count,
            ]);

        return Inertia::render('Admin/LibraryDocuments/Index', [
            'meta' => ['title' => $isSuperAdmin ? 'Pustaka' : 'Pustaka OPD'],
            'documents' => $documents,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $isSuperAdmin = $request->user()->hasRole('super-admin');

        return Inertia::render('Admin/LibraryDocuments/Form', [
            'meta' => ['title' => 'Tambah Dokumen'],
            'document' => null,
            'types' => $this->typeOptions(),
            'organisasis' => $isSuperAdmin ? $this->organisasiOptions() : [],
        ]);
    }

    public function store(LibraryDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $validated = $request->safe()->except(['file', 'organisasi_id']);

        $organisasiId = $isSuperAdmin
            ? $request->validated('organisasi_id')
            : $user->organisasis()->first()?->id;

        if (! $isSuperAdmin && $organisasiId === null) {
            return back()->with('error', 'Akun Anda belum terhubung dengan organisasi/OPD mana pun.');
        }

        $document = LibraryDocumentModel::create([
            ...$validated,
            'organisasi_id' => $organisasiId,
            'uploaded_by' => $user->id,
        ]);

        $document->addMediaFromRequest('file')->toMediaCollection('file');

        return redirect()
            ->route('admin.library-documents.index')
            ->with('success', 'Dokumen berhasil ditambahkan.');
    }

    public function edit(Request $request, LibraryDocumentModel $libraryDocument): Response
    {
        $this->authorizeAccess($request, $libraryDocument);

        $libraryDocument->load('organisasi:id,name');
        $file = $libraryDocument->getFirstMedia('file');
        $isSuperAdmin = $request->user()->hasRole('super-admin');

        return Inertia::render('Admin/LibraryDocuments/Form', [
            'meta' => ['title' => 'Ubah Dokumen'],
            'document' => [
                'id' => $libraryDocument->id,
                'title' => $libraryDocument->title,
                'nomor_dokumen' => $libraryDocument->nomor_dokumen,
                'penyusun' => $libraryDocument->penyusun,
                'tahun_terbit' => $libraryDocument->tahun_terbit,
                'tanggal_terbit' => $libraryDocument->tanggal_terbit?->toDateString(),
                'description' => $libraryDocument->description,
                'type' => $libraryDocument->type,
                'is_public' => $libraryDocument->is_public,
                'organisasi_id' => $libraryDocument->organisasi_id,
                'organisasi' => $libraryDocument->organisasi?->name,
                'file' => $file === null ? null : [
                    'name' => $file->file_name,
                    'size' => $file->human_readable_size,
                    'url' => $file->getUrl(),
                ],
            ],
            'types' => $this->typeOptions(),
            'organisasis' => $isSuperAdmin ? $this->organisasiOptions() : [],
        ]);
    }

    public function update(LibraryDocumentRequest $request, LibraryDocumentModel $libraryDocument): RedirectResponse
    {
        $this->authorizeAccess($request, $libraryDocument);

        $isSuperAdmin = $request->user()->hasRole('super-admin');
        $validated = $request->safe()->except(['file', 'organisasi_id']);

        if ($isSuperAdmin) {
            $validated['organisasi_id'] = $request->validated('organisasi_id');
        }

        $libraryDocument->update($validated);

        if ($request->hasFile('file')) {
            $libraryDocument->addMediaFromRequest('file')->toMediaCollection('file');
        }

        return redirect()
            ->route('admin.library-documents.index')
            ->with('success', 'Dokumen berhasil diperbarui.');
    }

    public function destroy(Request $request, LibraryDocumentModel $libraryDocument): RedirectResponse
    {
        $this->authorizeAccess($request, $libraryDocument);

        $libraryDocument->delete();

        return redirect()
            ->route('admin.library-documents.index')
            ->with('success', 'Dokumen berhasil dihapus.');
    }

    private function authorizeAccess(Request $request, LibraryDocumentModel $document): void
    {
        $user = $request->user();

        if ($user->hasRole('super-admin')) {
            return;
        }

        abort_unless(
            $user->organisasis()->where('organisasis.id', $document->organisasi_id)->exists(),
            403,
        );
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function typeOptions(): array
    {
        $labels = [
            'regulasi' => 'Regulasi',
            'panduan' => 'Panduan',
            'laporan' => 'Laporan',
            'lainnya' => 'Lainnya',
        ];

        return array_map(
            fn (string $type): array => ['id' => $type, 'name' => $labels[$type]],
            LibraryDocumentRequest::TYPES,
        );
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
}
