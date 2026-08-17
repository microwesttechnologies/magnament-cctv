<?php

declare(strict_types=1);

namespace App\Support\Cache;

/**
 * Claves explícitas. El driver database no soporta tags; invalidar por nombre.
 */
final class CacheKeys
{
    public const DASHBOARD_SNAPSHOT = 'dashboard.snapshot.global';

    public const PROJECTS_STATS = 'projects.stats.global';

    public const PROJECT_PICKER = 'catalog.projects.picker';

    public const STAFF_ACTIVE_TECHNICIANS = 'catalog.staff.technicians.active';

    public const SETTINGS_VAT = 'settings.vat_rate_percent';
}
