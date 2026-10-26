<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InnovationArchiveRequest;
use App\Models\InnovationCategoryModel;
use App\Models\InnovationModel;
use App\Models\OrganisasiModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InnovationArchiveController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Innovations/ArchiveForm', [
            'meta' => ['title' => 'Input Arsip Inovasi Lama'],
            'organisasis' => OrganisasiModel::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (OrganisasiModel $organisasi): array => [
                    'id' => $organisasi->id,
                    'name' => $organisasi->name,
                ])
                ->all(),
            'categories' => $this->categoryOptions(),
            'maxYear' => now()->year,
        ]);
    }

    public function store(InnovationArchiveRequest $request): RedirectResponse
    {
        $validated = $request->safe()->only([
            'organisasi_id',
            'category_id',
            'title',
            'jenis_inovasi',
            'description',
            'latar_belakang',
            'tahun',
            'published_at',
        ]);

        $innovation = InnovationModel::query()->create([
            ...$validated,
            'slug' => InnovationModel::uniqueSlug($validated['title']),
            'user_id' => Auth::id(),
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
