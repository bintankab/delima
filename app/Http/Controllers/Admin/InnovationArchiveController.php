<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JenisInisiator;
use App\Enums\JenisInovasi;
use App\Enums\TahapanInovasi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InnovationArchiveRequest;
use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InnovationArchiveController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Innovations/A-Innovations-ArchiveForm', [
            'meta' => ['title' => 'Input Arsip Inovasi Lama'],
            'inisiatorOptions' => JenisInisiator::options(),
            'organisasis' => $this->organisasiOptions(),
            'categories' => $this->categoryOptions(),
            'jenisOptions' => JenisInovasi::options(),
            'tahapanOptions' => TahapanInovasi::options(),
            'maxYear' => now()->year,
        ]);
    }

    public function store(InnovationArchiveRequest $request): RedirectResponse
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

        $innovation = InnovationModel::query()->create([
            ...$validated,
            'slug' => InnovationModel::uniqueSlug($validated['title']),
            'user_id' => $this->archiveOwnerId($request),
            'status' => 'published',
            'origin' => 'archive',
        ]);

        if ($request->hasFile('cover')) {
            $innovation->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        foreach ($this->documents($request) as $document) {
            $innovation->addMedia($document)->toMediaCollection('documents');
        }

        return redirect()
            ->route('admin.innovations.index')
            ->with('success', 'Arsip inovasi berhasil ditambahkan.');
    }

    private function archiveOwnerId(InnovationArchiveRequest $request): int
    {
        if ($request->validated('inisiator') === JenisInisiator::ASN->value) {
            return (int) $request->validated('user_id');
        }

        if ($request->validated('inisiator') !== JenisInisiator::OPD->value) {
            return (int) Auth::id();
        }

        return (int) OrganisasiModel::query()
            ->findOrFail($request->integer('organisasi_id'))
            ->primaryAdminOpdUserId();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function organisasiOptions(): array
    {
        return OrganisasiModel::query()
            ->with(['asnUsers' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (OrganisasiModel $organisasi): array => [
                'id' => $organisasi->id,
                'name' => $organisasi->name,
                'asn_users' => $organisasi->asnUsers
                    ->map(fn (UserModel $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                    ])
                    ->values()
                    ->all(),
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
    private function documents(InnovationArchiveRequest $request): array
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
