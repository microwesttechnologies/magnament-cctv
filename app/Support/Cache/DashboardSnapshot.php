<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Models\InstallationOrder;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\ServiceOrder;
use App\Models\TraceabilityEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class DashboardSnapshot
{
    /**
     * KPIs globales (no hay tenant). El nombre del usuario se rendera en la vista, no aquí.
     *
     * @return array{stats: array<string, int>, attention: Collection, recentActivity: Collection}
     */
    public function get(): array
    {
        return Cache::remember(CacheKeys::DASHBOARD_SNAPSHOT, CacheTtl::DASHBOARD, function (): array {
            $projectCounts = Project::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->whereIn('status', ['activo', 'instalacion'])
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $serviceOrderCounts = ServiceOrder::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $stats = [
                'projects_active' => (int) ($projectCounts['activo'] ?? 0),
                'projects_installing' => (int) ($projectCounts['instalacion'] ?? 0),
                'quotations_pending' => Quotation::query()->where('status', 'borrador')->count(),
                'orders_open' => InstallationOrder::query()
                    ->whereIn('status', ['pendiente', 'en_progreso'])
                    ->count(),
                'service_orders_pendiente' => (int) ($serviceOrderCounts['pendiente'] ?? 0),
                'service_orders_asignada' => (int) ($serviceOrderCounts['asignada'] ?? 0),
                'service_orders_en_proceso' => (int) ($serviceOrderCounts['en_proceso'] ?? 0),
                'service_orders_resuelta' => (int) ($serviceOrderCounts['resuelta'] ?? 0),
                'service_orders_cancelada' => (int) ($serviceOrderCounts['cancelada'] ?? 0),
            ];

            Log::debug('[DashboardSnapshot.get] snapshot rebuilt', [
                'stats' => $stats,
            ]);

            return [
                'stats' => $stats,
                'attention' => Quotation::query()
                    ->select(['id', 'project_id', 'code', 'status', 'updated_at'])
                    ->with('project:id,name')
                    ->where('status', 'borrador')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get(),
                'recentActivity' => TraceabilityEvent::query()
                    ->select(['id', 'project_id', 'event_type', 'title', 'created_at'])
                    ->with('project:id,name')
                    ->latest('created_at')
                    ->limit(8)
                    ->get(),
            ];
        });
    }
}
