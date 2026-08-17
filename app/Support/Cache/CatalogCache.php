<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Models\Project;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CatalogCache
{
    /** @return Collection<int, Project> */
    public function projectsPicker(): Collection
    {
        return Cache::remember(
            CacheKeys::PROJECT_PICKER,
            CacheTtl::CATALOG,
            fn (): Collection => Project::query()->orderBy('name')->get(['id', 'name', 'code']),
        );
    }

    /** @return Collection<int, Staff> */
    public function activeTechnicians(): Collection
    {
        return Cache::remember(
            CacheKeys::STAFF_ACTIVE_TECHNICIANS,
            CacheTtl::CATALOG,
            fn (): Collection => Staff::query()
                ->select(['id', 'name', 'document_number'])
                ->where('role', 'tecnico')
                ->where('status', 'activo')
                ->orderBy('name')
                ->get(),
        );
    }
}
