<?php

declare(strict_types=1);

namespace App\Support\Cache;

use App\Models\AppSetting;
use App\Models\Dvr;
use App\Models\FloorPlan;
use App\Models\InstallationOrder;
use App\Models\Project;
use App\Models\ProjectCamera;
use App\Models\Quotation;
use App\Models\Staff;
use App\Models\TraceabilityEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class CacheInvalidator
{
    public static function dashboard(): void
    {
        Cache::forget(CacheKeys::DASHBOARD_SNAPSHOT);
    }

    public static function projectStats(): void
    {
        Cache::forget(CacheKeys::PROJECTS_STATS);
        self::dashboard();
    }

    public static function projectCatalog(): void
    {
        Cache::forget(CacheKeys::PROJECT_PICKER);
    }

    public static function staffCatalog(): void
    {
        Cache::forget(CacheKeys::STAFF_ACTIVE_TECHNICIANS);
    }

    public static function settings(): void
    {
        Cache::forget(CacheKeys::SETTINGS_VAT);
        Cache::forget(CacheKeys::SETTINGS_COMPANY);
    }

    public static function projectPlans(int $projectId): void
    {
        Cache::forget(CacheKeys::projectPlans($projectId));
    }

    public static function forModel(Model $model): void
    {
        if ($model instanceof Project) {
            self::projectStats();
            self::projectCatalog();
            self::projectPlans((int) $model->id);

            return;
        }

        if ($model instanceof FloorPlan) {
            self::projectPlans((int) $model->project_id);

            return;
        }

        if ($model instanceof ProjectCamera) {
            self::projectPlans((int) $model->project_id);

            return;
        }

        if ($model instanceof Dvr) {
            self::projectStats();
            self::projectPlans((int) $model->project_id);

            return;
        }

        if ($model instanceof Quotation || $model instanceof InstallationOrder || $model instanceof TraceabilityEvent) {
            self::dashboard();

            return;
        }

        if ($model instanceof Staff) {
            self::staffCatalog();

            return;
        }

        if ($model instanceof AppSetting) {
            self::settings();
        }
    }
}
