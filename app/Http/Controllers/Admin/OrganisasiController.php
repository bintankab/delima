<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganisasiRequest;
use App\Models\OrganisasiModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganisasiController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, [
            'name', 'type', 'code', 'contact_person', 'is_active', 'library_documents_count',
        ]);

        $organisasis = OrganisasiModel::query()
            ->withCount(['users', 'innovations', 'risets', 'competitionSubmissions', 'libraryDocuments'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->orderBy('name'),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (OrganisasiModel $organisasi): array => [
                'id' => $organisasi->id,
                'name' => $organisasi->name,
                'type' => $organisasi->type,
                'code' => $organisasi->code,
                'contact_person' => $organisasi->contact_person,
                'phone' => $organisasi->phone,
                'email' => $organisasi->email,
                'is_active' => $organisasi->is_active,
                'logo_thumb' => $organisasi->getFirstMediaUrl('logo', 'thumb') ?: null,
                'documents_count' => $organisasi->library_documents_count,
                'can_delete' => $organisasi->users_count === 0
                    && $organisasi->innovations_count === 0
                    && $organisasi->risets_count === 0
                    && $organisasi->competition_submissions_count === 0
                    && $organisasi->library_documents_count === 0,
            ]);

        return Inertia::render('Admin/Organisasis/Index', [
            'meta' => ['title' => 'Organisasi/OPD'],
            'organisasis' => $organisasis,
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
        return Inertia::render('Admin/Organisasis/Form', [
            'meta' => ['title' => 'Tambah Organisasi/OPD'],
            'organisasi' => null,
        ]);
    }

    public function store(OrganisasiRequest $request): RedirectResponse
    {
        $validated = $request->safe()->except('logo');

        $organisasi = OrganisasiModel::create($validated);

        if ($request->hasFile('logo')) {
            $organisasi->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()
            ->route('admin.organisasis.index')
            ->with('success', 'Organisasi berhasil ditambahkan.');
    }

    public function edit(OrganisasiModel $organisasi): Response
    {
        return Inertia::render('Admin/Organisasis/Form', [
            'meta' => ['title' => 'Ubah Organisasi/OPD'],
            'organisasi' => $this->detail($organisasi),
        ]);
    }

    public function update(OrganisasiRequest $request, OrganisasiModel $organisasi): RedirectResponse
    {
        $validated = $request->safe()->except('logo');

        $organisasi->update($validated);

        if ($request->hasFile('logo')) {
            $organisasi->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        return redirect()
            ->route('admin.organisasis.index')
            ->with('success', 'Organisasi berhasil diperbarui.');
    }

    public function destroy(OrganisasiModel $organisasi): RedirectResponse
    {
        $organisasi->loadCount(['users', 'innovations', 'risets', 'competitionSubmissions', 'libraryDocuments']);

        if ($organisasi->users_count > 0
            || $organisasi->innovations_count > 0
            || $organisasi->risets_count > 0
            || $organisasi->competition_submissions_count > 0
            || $organisasi->library_documents_count > 0) {
            return back()->with('error', 'Organisasi masih memiliki pengguna atau data terkait.');
        }

        $organisasi->delete();

        return redirect()
            ->route('admin.organisasis.index')
            ->with('success', 'Organisasi berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(OrganisasiModel $organisasi): array
    {
        $logo = $organisasi->getFirstMedia('logo');

        return [
            'id' => $organisasi->id,
            'name' => $organisasi->name,
            'type' => $organisasi->type,
            'code' => $organisasi->code,
            'address' => $organisasi->address,
            'contact_person' => $organisasi->contact_person,
            'phone' => $organisasi->phone,
            'email' => $organisasi->email,
            'is_active' => $organisasi->is_active,
            'logo' => $logo === null ? null : [
                'id' => $logo->id,
                'url' => $logo->getUrl(),
                'thumb' => $logo->getUrl('thumb'),
            ],
        ];
    }
}
