<?php

namespace App\Http\Controllers;

use App\Models\InstallationOrder;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\TraceabilityEvent;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'projects_active' => Project::query()->where('status', 'activo')->count(),
            'projects_installing' => Project::query()->where('status', 'instalacion')->count(),
            'quotations_pending' => Quotation::query()->where('status', 'borrador')->count(),
            'orders_open' => InstallationOrder::query()->whereIn('status', ['pendiente', 'en_progreso'])->count(),
        ];

        $attention = Quotation::query()
            ->with('project:id,name')
            ->where('status', 'borrador')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentActivity = TraceabilityEvent::query()
            ->with(['project:id,name', 'user:id,name'])
            ->latest('created_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact('stats', 'attention', 'recentActivity'));
    }
}
