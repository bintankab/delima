<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RisetArchiveRequest;
use App\Models\OrganisasiModel;
use App\Models\RisetCategoryModel;
use App\Models\RisetModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RisetArchiveController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Risets/ArchiveForm', [
            'meta' => ['title' => 'Input Arsip Riset Lama'],
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

    public function store(RisetArchiveRequest $request): RedirectResponse
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

        $riset = RisetModel::query()->create([
            ...$validated,
            'slug' => RisetModel::uniqueSlug($validated['title']),
            'user_id' => Auth::id(),
            'status' => 'published',
            'origin' => 'archive',
        ]);

        if ($request->hasFile('cover')) {
            $riset->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        foreach ($this->documents($request) as $document) {
            $riset->addMedia($document)->toMediaCollection('documents');
        }

        return redirect()
            ->route('admin.risets.index')
            ->with('success', 'Arsip riset berhasil ditambahkan.');
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
    private function documents(RisetArchiveRequest $request): array
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
