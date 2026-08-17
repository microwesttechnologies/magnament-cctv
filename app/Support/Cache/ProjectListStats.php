<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Models\Dvr;
use App\Models\Project;
use Illuminate\Support\Facades\Cache;

final class ProjectListStats
{
    /**
     * @return array{activos: int, instalacion: int, mantenimiento: int, camaras: int}
     */
    public function get(): array
    {
        return Cache::remember(CacheKeys::PROJECTS_STATS, CacheTtl::LIST_STATS, function (): array {
            $counts = Project::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->whereIn('status', ['activo', 'instalacion', 'mantenimiento'])
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            return [
                'activos' => (int) ($counts['activo'] ?? 0),
                'instalacion' => (int) ($counts['instalacion'] ?? 0),
                'mantenimiento' => (int) ($counts['mantenimiento'] ?? 0),
                'camaras' => (int) Dvr::query()->sum('ports'),
            ];
        });
    }
}
