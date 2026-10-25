<?php

namespace App\Http\Controllers;

use App\Models\Innovation;
use App\Models\News;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    public function home(): Response
    {
        $latestNews = News::where('status', 'published')
            ->latest('published_at')
            ->take(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at']);

        $topInnovations = Innovation::where('status', 'published')
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
}
