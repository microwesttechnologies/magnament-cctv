<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\Dvr;
use App\Models\FloorPlan;
use App\Models\InstallationOrder;
use App\Models\Project;
use App\Models\ProjectCamera;
use App\Models\Quotation;
use App\Models\Staff;
use App\Models\TraceabilityEvent;
use App\Observers\CacheInvalidationObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $observer = CacheInvalidationObserver::class;
        Project::observe($observer);
        FloorPlan::observe($observer);
        ProjectCamera::observe($observer);
        Dvr::observe($observer);
        Quotation::observe($observer);
        InstallationOrder::observe($observer);
        TraceabilityEvent::observe($observer);
        Staff::observe($observer);
        AppSetting::observe($observer);

        $this->registerDevelopmentQueryLog();
    }

    private function registerDevelopmentQueryLog(): void
    {
        if ($this->app->runningUnitTests() || $this->app->isProduction()) {
            return;
        }

        if (! (bool) config('app.query_log', false)) {
            return;
        }

        $slowMs = (int) config('app.query_slow_ms', 100);

        DB::listen(function ($query) use ($slowMs): void {
            if ($query->time < $slowMs) {
                return;
            }

            Log::warning('[query] slow', [
                'sql' => $query->sql,
                'time_ms' => $query->time,
            ]);
        });
    }
}
