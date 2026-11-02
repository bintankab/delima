<?php

namespace App\Http\Controllers;

use App\Enums\JenisInisiator;
use App\Enums\PageLayout;
use App\Http\Requests\Admin\LibraryDocumentRequest;
use App\Models\CategoryModel;
use App\Models\InnovationModel;
use App\Models\LibraryDocumentModel;
use App\Models\OrganisasiModel;
use App\Models\PageModel;
use App\Models\PostModel;
use App\Models\RisetModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
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
        $latestPosts = PostModel::query()
            ->where('status', 'published')
            ->with(['user:id,name', 'media'])
            ->latest('published_at')
            ->take(14)
            ->get(['id', 'user_id', 'title', 'slug', 'excerpt', 'published_at'])
            ->map(function (PostModel $post): array {
                $cover = $post->getFirstMedia('cover');

                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'published_at' => $post->published_at?->toDateString(),
                    'author' => $post->user?->name,
                    'cover' => $cover === null
                        ? null
                        : ($cover->hasGeneratedConversion('medium') ? $cover->getUrl('medium') : $cover->getUrl()),
                ];
            })
            ->all();

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
            'latestPosts' => $latestPosts,
            'topInnovations' => $topInnovations,
        ]);
    }

    public function beritaShow(PostModel $post): Response
    {
        abort_unless($post->status === 'published', 404);

        $this->recordView($post, 'post');

        $post->load(['user:id,name', 'categories:id,name']);
        $cover = $post->getFirstMedia('cover');

        return Inertia::render('Public/Posts/Show', [
            'meta' => [
                'title' => $post->title,
                'description' => $post->excerpt ?: $this->excerpt($post->content, 30),
            ],
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'published_at' => $post->published_at?->toDateString(),
                'author' => $post->user?->name,
                'views_count' => $post->views_count,
                'read_minutes' => $this->readMinutes($post->content),
                'categories' => $post->categories
                    ->sortBy('name')
                    ->map(fn (CategoryModel $category): array => ['name' => $category->name])
                    ->values()
                    ->all(),
                'cover' => $cover === null ? null : $cover->getUrl(),
            ],
            'relatedPosts' => $this->relatedPosts($post),
        ]);
    }

    public function pageShow(PageModel $page): Response
    {
        abort_unless($page->status === 'published', 404);

        $this->recordView($page, 'page');

        $layout = PageLayout::from($page->layout);

        return Inertia::render($layout->component(), [
            'meta' => [
                'title' => $page->title,
                'description' => $page->excerpt ?: $this->excerpt($page->content, 30),
            ],
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'excerpt' => $page->excerpt,
                'content' => $page->content,
                'fields' => $this->pageFields($page, $layout),
            ],
            'slides' => $layout->hasSlides() ? $this->pageSlides($page) : [],
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

    public function pustaka(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $type = $request->string('type')->trim()->toString();
        $type = in_array($type, LibraryDocumentRequest::TYPES, true) ? $type : null;
        $tahun = $request->integer('tahun') ?: null;
        $organisasiId = $request->integer('organisasi_id') ?: null;

        $documents = LibraryDocumentModel::query()
            ->where('is_public', true)
            ->with(['organisasi:id,name', 'media'])
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($escaped): void {
                    $query->where('title', 'like', $escaped)
                        ->orWhere('nomor_dokumen', 'like', $escaped);
                });
            })
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->when($tahun !== null, fn ($query) => $query->where('tahun_terbit', $tahun))
            ->when($organisasiId !== null, fn ($query) => $query->where('organisasi_id', $organisasiId))
            ->latest('tanggal_terbit')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (LibraryDocumentModel $document): array => [
                'id' => $document->id,
                'slug' => $document->slug,
                'title' => $document->title,
                'nomor_dokumen' => $document->nomor_dokumen,
                'penyusun' => $document->penyusun,
                'tahun_terbit' => $document->tahun_terbit,
                'tanggal_terbit' => $document->tanggal_terbit?->toDateString(),
                'description' => $this->excerpt($document->description, 40),
                'type' => $document->type,
                'type_label' => $this->libraryDocumentTypeLabel($document->type),
                'organisasi' => $document->organisasi?->name,
                'file_size' => $document->getFirstMedia('file')?->human_readable_size,
                'download_url' => route('pustaka.download', $document),
            ]);

        return Inertia::render('Public/LibraryDocuments/Index', [
            'meta' => [
                'title' => 'Pustaka',
                'description' => 'Dokumen publik Kabupaten Bintan: regulasi, panduan, laporan, dan arsip lainnya.',
            ],
            'documents' => $documents,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'tahun' => $tahun,
                'organisasi_id' => $organisasiId,
            ],
            'filterOptions' => [
                'types' => array_map(
                    fn (string $type): array => ['id' => $type, 'name' => $this->libraryDocumentTypeLabel($type)],
                    LibraryDocumentRequest::TYPES,
                ),
                'years' => LibraryDocumentModel::query()
                    ->where('is_public', true)
                    ->whereNotNull('tahun_terbit')
                    ->distinct()
                    ->orderByDesc('tahun_terbit')
                    ->pluck('tahun_terbit'),
                'organisasis' => OrganisasiModel::query()
                    ->whereHas('libraryDocuments', fn ($query) => $query->where('is_public', true))
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ],
        ]);
    }

    public function pustakaShow(LibraryDocumentModel $libraryDocument): Response
    {
        abort_unless($libraryDocument->is_public, 404);

        $this->recordView($libraryDocument, 'library-document');

        $libraryDocument->load('organisasi:id,name');
        $file = $libraryDocument->getFirstMedia('file');

        return Inertia::render('Public/LibraryDocuments/Show', [
            'meta' => [
                'title' => $libraryDocument->title,
                'description' => $this->excerpt($libraryDocument->description, 30),
            ],
            'document' => [
                'id' => $libraryDocument->id,
                'title' => $libraryDocument->title,
                'slug' => $libraryDocument->slug,
                'nomor_dokumen' => $libraryDocument->nomor_dokumen,
                'penyusun' => $libraryDocument->penyusun,
                'tahun_terbit' => $libraryDocument->tahun_terbit,
                'tanggal_terbit' => $libraryDocument->tanggal_terbit?->toDateString(),
                'description' => $libraryDocument->description,
                'type' => $libraryDocument->type,
                'type_label' => $libraryDocument->type
                    ? $this->libraryDocumentTypeLabel($libraryDocument->type)
                    : 'Dokumen',
                'organisasi' => $libraryDocument->organisasi?->name,
                'views_count' => $libraryDocument->views_count,
                'downloads_count' => $libraryDocument->downloads_count,
                'read_minutes' => $this->readMinutes($libraryDocument->description),
                'file' => $file === null ? null : [
                    'name' => $file->file_name,
                    'size' => $file->human_readable_size,
                    'url' => route('pustaka.download', $libraryDocument),
                ],
            ],
            'relatedDocuments' => $this->relatedLibraryDocuments($libraryDocument),
        ]);
    }

    public function pustakaDownload(LibraryDocumentModel $libraryDocument): RedirectResponse
    {
        abort_unless($libraryDocument->is_public, 404);

        $file = $libraryDocument->getFirstMedia('file');
        abort_unless($file !== null, 404);

        $libraryDocument->increment('downloads_count');

        return redirect($file->getUrl());
    }

    private function libraryDocumentTypeLabel(string $type): string
    {
        return match ($type) {
            'regulasi' => 'Regulasi',
            'panduan' => 'Panduan',
            'laporan' => 'Laporan',
            'lainnya' => 'Lainnya',
            default => $type,
        };
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
     * @return list<array<string, mixed>>
     */
    private function relatedPosts(PostModel $post): array
    {
        return PostModel::query()
            ->where('status', 'published')
            ->whereKeyNot($post->id)
            ->with(['user:id,name', 'media'])
            ->latest('published_at')
            ->take(2)
            ->get()
            ->map(function (PostModel $related): array {
                $cover = $related->getFirstMedia('cover');

                return [
                    'id' => $related->id,
                    'title' => $related->title,
                    'slug' => $related->slug,
                    'excerpt' => $related->excerpt,
                    'published_at' => $related->published_at?->toDateString(),
                    'author' => $related->user?->name,
                    'read_minutes' => $this->readMinutes($related->content),
                    'cover' => $cover === null
                        ? null
                        : ($cover->hasGeneratedConversion('thumb') ? $cover->getUrl('thumb') : $cover->getUrl()),
                ];
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function relatedLibraryDocuments(LibraryDocumentModel $document): array
    {
        return LibraryDocumentModel::query()
            ->where('is_public', true)
            ->whereKeyNot($document->id)
            ->when(
                $document->type,
                fn ($query) => $query->where('type', $document->type),
            )
            ->with('organisasi:id,name')
            ->latest('tanggal_terbit')
            ->take(2)
            ->get()
            ->map(fn (LibraryDocumentModel $related): array => [
                'id' => $related->id,
                'title' => $related->title,
                'slug' => $related->slug,
                'type_label' => $this->libraryDocumentTypeLabel((string) $related->type),
                'organisasi' => $related->organisasi?->name,
                'tahun_terbit' => $related->tahun_terbit,
            ])
            ->all();
    }

    /**
     * @return array{cards: list<array{title: string, body: string, url: string|null, thumb: string|null}>}
     */
    private function pageFields(PageModel $page, PageLayout $layout): array
    {
        if (! $layout->hasCards()) {
            return ['cards' => []];
        }

        $images = $page->getMedia('images')->keyBy('id');
        $cards = [];

        foreach ($page->fields['cards'] ?? [] as $card) {
            $media = $images->get($card['image_id'] ?? 0);
            $cards[] = [
                'title' => $card['title'] ?? '',
                'body' => $card['body'] ?? '',
                'url' => $media?->getUrl(),
                'thumb' => $this->mediaThumb($media),
            ];
        }

        return ['cards' => $cards];
    }

    /**
     * @return list<array{url: string, thumb: string|null, caption: string|null}>
     */
    private function pageSlides(PageModel $page): array
    {
        return $page->getMedia('slides')
            ->map(function (Media $media): array {
                $caption = $media->getCustomProperty('caption');

                return [
                    'url' => $media->getUrl(),
                    'thumb' => $this->mediaThumb($media),
                    'caption' => is_string($caption) && $caption !== '' ? $caption : null,
                ];
            })
            ->values()
            ->all();
    }

    private function mediaThumb(?Media $media): ?string
    {
        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl();
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
