<?php

namespace App\Http\Controllers;

use App\Models\InnovationModel;
use App\Models\NewsModel;
use App\Models\OrganisasiModel;
use App\Models\RisetModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PublicController extends Controller
{
    public function home(): Response
    {
        $latestNews = NewsModel::where('status', 'published')
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        $topInnovations = InnovationModel::where('status', 'published')
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(6)
            ->get(['id', 'title', 'slug', 'tahun']);

        return Inertia::render('Public/Home', [
            'meta' => [
                'title' => 'Beranda',
                'description' => 'Delima — pusat informasi dan pengetahuan inovasi daerah Kabupaten Bintan.',
            ],
            'latestNews' => $latestNews,
            'topInnovations' => $topInnovations,
        ]);
    }

    public function riset(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $tahun = $request->integer('tahun') ?: null;
        $organisasiId = $request->integer('organisasi_id') ?: null;

        $risets = RisetModel::query()
            ->where('status', 'published')
            ->with(['organisasi:id,name', 'category:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when($tahun !== null, fn ($query) => $query->where('tahun', $tahun))
            ->when($organisasiId !== null, fn ($query) => $query->where('organisasi_id', $organisasiId))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (RisetModel $riset): array => [
                'id' => $riset->id,
                'title' => $riset->title,
                'slug' => $riset->slug,
                'abstrak' => Str::words($riset->abstrak, 50),
                'tahun' => $riset->tahun,
                'organisasi' => $riset->organisasi?->name,
                'category' => $riset->category?->name,
                'cover' => $riset->getFirstMedia('cover')?->getUrl('thumb'),
            ]);

        return Inertia::render('Public/Riset/Index', [
            'meta' => [
                'title' => 'Riset',
                'description' => 'Daftar riset daerah Kabupaten Bintan — dokumentasi hasil penelitian dan kajian.',
            ],
            'risets' => $risets,
            'filters' => [
                'search' => $search,
                'tahun' => $tahun,
                'organisasi_id' => $organisasiId,
            ],
            'filterOptions' => [
                'years' => RisetModel::query()
                    ->where('status', 'published')
                    ->distinct()
                    ->orderByDesc('tahun')
                    ->pluck('tahun'),
                'organisasis' => OrganisasiModel::query()
                    ->whereHas('risets', fn ($query) => $query->where('status', 'published'))
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function risetShow(RisetModel $riset): Response
    {
        abort_unless($riset->status === 'published', 404);

        $riset->load(['organisasi:id,name', 'category:id,name']);
        $cover = $riset->getFirstMedia('cover');

        return Inertia::render('Public/Riset/Show', [
            'meta' => [
                'title' => $riset->title,
                'description' => Str::words($riset->abstrak, 30),
            ],
            'riset' => [
                'id' => $riset->id,
                'title' => $riset->title,
                'slug' => $riset->slug,
                'peneliti' => $riset->peneliti,
                'abstrak' => $riset->abstrak,
                'latar_belakang' => $riset->latar_belakang,
                'metodologi' => $riset->metodologi,
                'tahun' => $riset->tahun,
                'published_at' => $riset->published_at?->toDateString(),
                'organisasi' => $riset->organisasi?->name,
                'category' => $riset->category?->name,
                'cover' => $cover === null ? null : [
                    'url' => $cover->getUrl(),
                    'thumb' => $cover->getUrl('thumb'),
                ],
                'documents' => $riset->getMedia('documents')->map(fn ($media): array => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->human_readable_size,
                    'url' => $media->getUrl(),
                    'read_url' => route('riset.document', [$riset, $media]),
                ])->values()->all(),
            ],
        ]);
    }

    public function risetDocument(RisetModel $riset, Media $media): Response
    {
        abort_unless($riset->status === 'published', 404);
        abort_unless(
            $media->model_type === RisetModel::class
            && $media->model_id === $riset->id
            && $media->collection_name === 'documents',
            404,
        );

        return Inertia::render('Public/DocumentReader', [
            'meta' => [
                'title' => $media->file_name,
            ],
            'document' => [
                'name' => $media->file_name,
                'url' => $media->getUrl(),
            ],
            'back' => [
                'url' => route('riset.show', $riset),
                'label' => $riset->title,
            ],
        ]);
    }

    public function inovasi(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $tahun = $request->integer('tahun') ?: null;
        $organisasiId = $request->integer('organisasi_id') ?: null;

        $innovations = InnovationModel::query()
            ->where('status', 'published')
            ->with(['organisasi:id,name', 'category:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when($tahun !== null, fn ($query) => $query->where('tahun', $tahun))
            ->when($organisasiId !== null, fn ($query) => $query->where('organisasi_id', $organisasiId))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (InnovationModel $innovation): array => [
                'id' => $innovation->id,
                'title' => $innovation->title,
                'slug' => $innovation->slug,
                'description' => Str::words($innovation->description, 50),
                'jenis_inovasi' => $innovation->jenis_inovasi,
                'tahun' => $innovation->tahun,
                'organisasi' => $innovation->organisasi?->name,
                'category' => $innovation->category?->name,
                'cover' => $innovation->getFirstMedia('cover')?->getUrl('thumb'),
            ]);

        return Inertia::render('Public/Innovations/Index', [
            'meta' => [
                'title' => 'Inovasi',
                'description' => 'Etalase inovasi daerah Kabupaten Bintan.',
            ],
            'innovations' => $innovations,
            'filters' => [
                'search' => $search,
                'tahun' => $tahun,
                'organisasi_id' => $organisasiId,
            ],
            'filterOptions' => [
                'years' => InnovationModel::query()
                    ->where('status', 'published')
                    ->distinct()
                    ->orderByDesc('tahun')
                    ->pluck('tahun'),
                'organisasis' => OrganisasiModel::query()
                    ->whereHas('innovations', fn ($query) => $query->where('status', 'published'))
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function inovasiShow(InnovationModel $innovation): Response
    {
        abort_unless($innovation->status === 'published', 404);

        $innovation->load(['organisasi:id,name', 'category:id,name']);
        $cover = $innovation->getFirstMedia('cover');

        return Inertia::render('Public/Innovations/Show', [
            'meta' => [
                'title' => $innovation->title,
                'description' => Str::words($innovation->description, 30),
            ],
            'innovation' => [
                'id' => $innovation->id,
                'title' => $innovation->title,
                'slug' => $innovation->slug,
                'jenis_inovasi' => $innovation->jenis_inovasi,
                'description' => $innovation->description,
                'latar_belakang' => $innovation->latar_belakang,
                'tahun' => $innovation->tahun,
                'published_at' => $innovation->published_at?->toDateString(),
                'organisasi' => $innovation->organisasi?->name,
                'category' => $innovation->category?->name,
                'cover' => $cover === null ? null : [
                    'url' => $cover->getUrl(),
                    'thumb' => $cover->getUrl('thumb'),
                ],
                'documents' => $innovation->getMedia('documents')->map(fn ($media): array => [
                    'id' => $media->id,
                    'name' => $media->file_name,
                    'size' => $media->human_readable_size,
                    'url' => $media->getUrl(),
                    'read_url' => route('inovasi.document', [$innovation, $media]),
                ])->values()->all(),
            ],
        ]);
    }

    public function inovasiDocument(InnovationModel $innovation, Media $media): Response
    {
        abort_unless($innovation->status === 'published', 404);
        abort_unless(
            $media->model_type === InnovationModel::class
            && $media->model_id === $innovation->id
            && $media->collection_name === 'documents',
            404,
        );

        return Inertia::render('Public/DocumentReader', [
            'meta' => [
                'title' => $media->file_name,
            ],
            'document' => [
                'name' => $media->file_name,
                'url' => $media->getUrl(),
            ],
            'back' => [
                'url' => route('inovasi.show', $innovation),
                'label' => $innovation->title,
            ],
        ]);
    }
}
