<?php

namespace App\Http\Controllers;

use App\Models\InnovationModel;
use App\Models\NewsModel;
use App\Models\RisetModel;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

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

    public function riset(): Response
    {
        $risets = RisetModel::query()
            ->where('status', 'published')
            ->with(['organisasi:id,name', 'category:id,name'])
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
        ]);
    }

    public function inovasi(): Response
    {
        $innovations = InnovationModel::query()
            ->where('status', 'published')
            ->with(['organisasi:id,name', 'category:id,name'])
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
        ]);
    }
}
