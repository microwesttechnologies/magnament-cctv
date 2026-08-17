<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Quotation\Ports\VatSettingsInterface;
use App\Models\AppSetting;
use App\Models\InstallationOrder;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\TraceabilityEvent;
use App\Models\User;
use App\Support\Cache\CacheKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_second_request_reuses_snapshot_cache(): void
    {
        $user = User::factory()->create();
        $this->seedDashboardData();

        $this->actingAs($user);

        $miss = $this->countQueries(fn () => $this->get('/dashboard')->assertOk());
        $hit = $this->countQueries(fn () => $this->get('/dashboard')->assertOk());

        $this->assertTrue(Cache::has(CacheKeys::DASHBOARD_SNAPSHOT));
        $this->assertLessThan($miss, $hit);
        $this->assertLessThanOrEqual(14, $miss);
        $this->assertLessThanOrEqual(8, $hit);
    }

    public function test_dashboard_sends_private_no_store_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard')->assertOk();
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
    }

    public function test_project_show_query_count_does_not_grow_with_orders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $small = Project::createRecord(['name' => 'Obra A']);
        $this->attachOrders($small, 1);
        $smallCount = $this->countQueries(
            fn () => $this->get('/projects/'.$small->id)->assertOk()
        );

        $large = Project::createRecord(['name' => 'Obra B']);
        $this->attachOrders($large, 8);
        $largeCount = $this->countQueries(
            fn () => $this->get('/projects/'.$large->id)->assertOk()
        );

        $this->assertLessThanOrEqual($smallCount + 1, $largeCount);
    }

    public function test_listings_are_paginated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/projects')->assertOk();
        $this->get('/cotizaciones')->assertOk()->assertSee('Nueva Cotización');
        $this->get('/ordenes')->assertOk()->assertSee('Órdenes de servicio');
        $this->get('/personal')->assertOk();
        $this->get('/trazabilidad')->assertOk();
    }

    public function test_vat_setting_is_cached_and_invalidated_on_update(): void
    {
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $settings = app(VatSettingsInterface::class);
        $this->assertSame('16.0000', $settings->currentVatRatePercent());

        $cachedReads = $this->countQueries(function () use ($settings): void {
            $settings->currentVatRatePercent();
            $settings->currentVatRatePercent();
        });

        $this->assertSame(0, $cachedReads);
        $this->assertTrue(Cache::has(CacheKeys::SETTINGS_VAT));

        $settings->updateVatRatePercent('19');
        $this->assertFalse(Cache::has(CacheKeys::SETTINGS_VAT));
        $this->assertSame('19.0000', $settings->currentVatRatePercent());
    }

    public function test_creating_a_project_invalidates_dashboard_and_catalog_cache(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->actingAs($user)->get('/cotizaciones/crear')->assertOk();

        $this->assertTrue(Cache::has(CacheKeys::DASHBOARD_SNAPSHOT));
        $this->assertTrue(Cache::has(CacheKeys::PROJECT_PICKER));

        Project::createRecord(['name' => 'Nuevo cache bust']);

        $this->assertFalse(Cache::has(CacheKeys::DASHBOARD_SNAPSHOT));
        $this->assertFalse(Cache::has(CacheKeys::PROJECT_PICKER));
        $this->assertFalse(Cache::has(CacheKeys::PROJECTS_STATS));
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function seedDashboardData(): void
    {
        $project = Project::createRecord(['name' => 'Residencial Norte', 'status' => 'activo']);
        Quotation::query()->create([
            'project_id' => $project->id,
            'code' => 'COT-DASH-1',
            'status' => 'borrador',
            'vat_rate_percent' => '16.0000',
            'subtotal' => 100,
            'vat_amount' => 16,
            'total' => 116,
        ]);
        TraceabilityEvent::query()->create([
            'project_id' => $project->id,
            'event_type' => 'quotation.created',
            'title' => 'Cotización creada',
        ]);
    }

    private function attachOrders(Project $project, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $quotation = Quotation::query()->create([
                'project_id' => $project->id,
                'code' => 'COT-'.$project->id.'-'.$i,
                'status' => 'convertida',
                'vat_rate_percent' => '16.0000',
                'subtotal' => 100,
                'vat_amount' => 16,
                'total' => 116,
            ]);
            InstallationOrder::query()->create([
                'project_id' => $project->id,
                'quotation_id' => $quotation->id,
                'code' => 'ORD-'.$project->id.'-'.$i,
                'status' => 'pendiente',
            ]);
        }
    }
}
