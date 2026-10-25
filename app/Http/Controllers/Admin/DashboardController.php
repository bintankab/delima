<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSubmission;
use App\Models\Consultation;
use App\Models\Idea;
use App\Models\Innovation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $innovationQuery = Innovation::query();
        if (! $isSuperAdmin && $user->hasRole('admin-opd')) {
            $innovationQuery->whereIn('organisasi_id', $user->organisasis()->pluck('organisasis.id'));
        }

        $statusCounts = (clone $innovationQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statuses = ['draft', 'submitted', 'under_review', 'reviewed', 'revision_needed', 'published', 'rejected', 'archived'];

        return Inertia::render('Admin/Dashboard/Analytics', [
            'meta' => ['title' => 'Dashboard'],
            'stats' => [
                'total_innovations' => (clone $innovationQuery)->count(),
                'published_innovations' => (clone $innovationQuery)->where('status', 'published')->count(),
                'open_ideas' => Idea::where('status', 'new')->count(),
                'active_consultations' => Consultation::whereIn('status', ['open', 'in_progress'])->count(),
                'competition_submissions' => CompetitionSubmission::count(),
            ],
            'innovationsByStatus' => [
                'labels' => array_map(fn ($s) => ucwords(str_replace('_', ' ', $s)), $statuses),
                'data' => array_map(fn ($s) => (int) ($statusCounts[$s] ?? 0), $statuses),
            ],
            'latestSubmissions' => (clone $innovationQuery)
                ->with(['organisasi:id,name', 'category:id,name'])
                ->latest()
                ->take(6)
                ->get(['id', 'title', 'organisasi_id', 'category_id', 'status', 'tahun', 'created_at']),
        ]);
    }
}
