<?php

namespace App\Http\Controllers;

use App\Enums\JenisInisiator;
use App\Models\InnovationModel;
use App\Models\NewsModel;
use App\Models\OrganisasiModel;
use App\Models\RisetModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            ->latest('implementation_at')
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
                'abstrak' => $this->excerpt($riset->abstrak, 50),
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

        $this->recordView($riset, 'riset');

        $riset->load(['organisasi:id,name', 'category:id,name']);
        $cover = $riset->getFirstMedia('cover');

        return Inertia::render('Public/Riset/Show', [
            'meta' => [
                'title' => $riset->title,
                'description' => $this->excerpt($riset->abstrak, 30),
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
                'views_count' => $riset->views_count,
                'read_minutes' => $this->readMinutes($riset->abstrak),
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
            'relatedRisets' => $this->relatedRisets($riset),
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
        $inisiator = $request->string('inisiator')->trim()->toString() ?: null;

        $innovations = InnovationModel::query()
            ->where('status', 'published')
            ->with(['category:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%');
            })
            ->when($tahun !== null, fn ($query) => $query->where('tahun', $tahun))
            ->when($inisiator !== null, fn ($query) => $query->where('inisiator', $inisiator))
            ->latest('implementation_at')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (InnovationModel $innovation): array => [
                'id' => $innovation->id,
                'title' => $innovation->title,
                'slug' => $innovation->slug,
                'description' => $this->excerpt($innovation->description, 50),
                'jenis_inovasi_label' => $innovation->jenis_inovasi?->label(),
                'tahun' => $innovation->tahun,
                'inisiator_label' => $innovation->inisiator?->label(),
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
                'inisiator' => $inisiator,
            ],
            'filterOptions' => [
                'years' => InnovationModel::query()
                    ->where('status', 'published')
                    ->distinct()
                    ->orderByDesc('tahun')
                    ->pluck('tahun'),
                'inisiators' => JenisInisiator::options(),
            ],
        ]);
    }

    public function inovasiShow(InnovationModel $innovation): Response
    {
        abort_unless($innovation->status === 'published', 404);

        $this->recordView($innovation, 'innovation');

        $innovation->load(['category:id,name']);
        $cover = $innovation->getFirstMedia('cover');

        return Inertia::render('Public/Innovations/Show', [
            'meta' => [
                'title' => $innovation->title,
                'description' => $this->excerpt($innovation->description, 30),
            ],
            'innovation' => [
                'id' => $innovation->id,
                'title' => $innovation->title,
                'slug' => $innovation->slug,
                'jenis_inovasi_label' => $innovation->jenis_inovasi?->label(),
                'description' => $innovation->description,
                'latar_belakang' => $innovation->latar_belakang,
                'manfaat' => $innovation->manfaat,
                'link_video' => $innovation->link_video,
                'link_file_pendukung' => $innovation->link_file_pendukung,
                'tahun' => $innovation->tahun,
                'testing_at' => $innovation->testing_at?->toDateString(),
                'implementation_at' => $innovation->implementation_at?->toDateString(),
                'inisiator_label' => $innovation->inisiator?->label(),
                'category' => $innovation->category?->name,
                'views_count' => $innovation->views_count,
                'read_minutes' => $this->readMinutes($innovation->description),
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
            'relatedInnovations' => $this->relatedInnovations($innovation),
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

    /**
     * Plain-text excerpt of Quill-authored HTML content, truncated to the given word count.
     */
    private function excerpt(?string $html, int $words): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        return Str::words(trim($text), $words);
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
            ->where('status', 'published')
            ->whereKeyNot($innovation->id)
            ->when(
                $innovation->category_id,
                fn ($query) => $query->where('category_id', $innovation->category_id),
            )
            ->latest('implementation_at')
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
     * @return list<array<string, mixed>>
     */
    private function relatedRisets(RisetModel $riset): array
    {
        return RisetModel::query()
            ->where('status', 'published')
            ->whereKeyNot($riset->id)
            ->when(
                $riset->category_id,
                fn ($query) => $query->where('category_id', $riset->category_id),
            )
            ->with('organisasi:id,name')
            ->latest('published_at')
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
     * Count one public view for the model, deduplicated per visitor (IP + user agent) for 24 hours.
     */
    private function recordView(Model $model, string $type): void
    {
        $fingerprint = sha1(request()->ip().'|'.request()->userAgent());
        $key = "public-view:{$type}:{$model->getKey()}:{$fingerprint}";

        if (Cache::add($key, true, now()->addDay())) {
            $model->increment('views_count');
        }
    }
}
