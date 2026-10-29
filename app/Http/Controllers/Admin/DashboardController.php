<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompetitionSubmissionModel;
use App\Models\ConsultationModel;
use App\Models\IdeaModel;
use App\Models\InnovationModel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isSuperAdmin = $user->hasRole('super-admin');

        $innovationQuery = InnovationModel::query();
        if (! $isSuperAdmin && $user->hasRole('admin-opd')) {
            $ownOrganisasiIds = $user->organisasis()->pluck('organisasis.id');
            $innovationQuery->whereHas(
                'user.organisasis',
                fn ($query) => $query->whereIn('organisasis.id', $ownOrganisasiIds),
            );
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
                'open_ideas' => IdeaModel::where('status', 'new')->count(),
                'active_consultations' => ConsultationModel::whereIn('status', ['open', 'in_progress'])->count(),
                'competition_submissions' => CompetitionSubmissionModel::count(),
            ],
            'innovationsByStatus' => [
                'labels' => array_map(fn ($s) => ucwords(str_replace('_', ' ', $s)), $statuses),
                'data' => array_map(fn ($s) => (int) ($statusCounts[$s] ?? 0), $statuses),
            ],
            'latestSubmissions' => (clone $innovationQuery)
                ->with(['category:id,name'])
                ->latest()
                ->take(6)
                ->get(['id', 'title', 'inisiator', 'category_id', 'status', 'tahun', 'created_at'])
                ->map(fn (InnovationModel $innovation): array => [
                    'id' => $innovation->id,
                    'title' => $innovation->title,
                    'inisiator_label' => $innovation->inisiator?->label(),
                    'category' => $innovation->category?->name,
                    'status' => $innovation->status,
                    'tahun' => $innovation->tahun,
                ]),
        ]);
    }
}
