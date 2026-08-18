<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationStandaloneEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotations_index_has_new_quotation_button(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/cotizaciones')
            ->assertOk()
            ->assertSee('Nueva cotización');
    }

    public function test_standalone_create_opens_modal_on_quotations_index(): void
    {
        $user = User::factory()->create();
        Project::createRecord(['name' => 'Residencial Norte']);

        $this->actingAs($user)
            ->get('/cotizaciones/crear')
            ->assertRedirect(route('cotizaciones', ['crear' => 1]));

        $this->actingAs($user)
            ->get('/cotizaciones?crear=1')
            ->assertOk()
            ->assertSee('Nueva cotización')
            ->assertSee('Selecciona un proyecto')
            ->assertSee('+ Crear nuevo proyecto')
            ->assertSee('Residencial Norte');
    }

    public function test_quick_project_can_be_created_from_quotations_module(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/cotizaciones/proyectos', ['name' => 'Proyecto Rápido']);

        $response->assertCreated()
            ->assertJsonPath('name', 'Proyecto Rápido');

        $this->assertDatabaseHas('projects_tb', ['name' => 'Proyecto Rápido']);
    }

    public function test_standalone_quotation_can_be_stored_for_selected_project(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Obra Centro']);
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                '_form' => 'quotation-create',
                'project_id' => $project->id,
                'work_description' => 'Instalación CCTV',
                'lines' => [[
                    'product_name' => 'Cámara IP',
                    'quantity' => '2',
                    'brand' => 'Hikvision',
                    'serial' => 'SN-1',
                    'unit_price' => '100',
                ]],
            ])
            ->assertRedirect(route('cotizaciones'));

        $this->assertDatabaseHas('quotations_tb', [
            'project_id' => $project->id,
            'work_description' => 'Instalación CCTV',
            'vat_rate_percent' => '16.0000',
        ]);
        $this->assertSame(1, Quotation::query()->count());
        $this->assertSame(1, QuotationLine::query()->count());
    }

    public function test_standalone_quotation_can_be_created_without_vat_setting(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Sin IVA global']);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                '_form' => 'quotation-create',
                'project_id' => $project->id,
                'work_description' => 'Cobertura perimetral',
                'lines' => [[
                    'product_name' => 'DVR',
                    'quantity' => '1',
                    'unit_price' => '500',
                ]],
            ])
            ->assertRedirect(route('cotizaciones'));

        $this->assertDatabaseHas('quotations_tb', [
            'project_id' => $project->id,
            'vat_rate_percent' => '0.0000',
            'vat_amount' => '0.00',
            'subtotal' => '500.00',
            'total' => '500.00',
        ]);
    }

    public function test_standalone_quotation_accepts_explicit_zero_vat_rate(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'IVA cero']);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                '_form' => 'quotation-create',
                'project_id' => $project->id,
                'work_description' => 'Sin impuesto',
                'vat_rate_percent' => '0',
                'lines' => [[
                    'product_name' => 'Sensor',
                    'quantity' => '3',
                    'unit_price' => '50',
                ]],
            ])
            ->assertRedirect(route('cotizaciones'));

        $this->assertDatabaseHas('quotations_tb', [
            'project_id' => $project->id,
            'vat_rate_percent' => '0.0000',
            'vat_amount' => '0.00',
            'subtotal' => '150.00',
            'total' => '150.00',
        ]);
    }

    public function test_standalone_quotation_accepts_explicit_vat_rate_override(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'IVA 19']);
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                '_form' => 'quotation-create',
                'project_id' => $project->id,
                'work_description' => 'Cliente exento parcial',
                'vat_rate_percent' => '19',
                'lines' => [[
                    'product_name' => 'Kit CCTV',
                    'quantity' => '1',
                    'unit_price' => '1000',
                ]],
            ])
            ->assertRedirect(route('cotizaciones'));

        $this->assertDatabaseHas('quotations_tb', [
            'project_id' => $project->id,
            'vat_rate_percent' => '19.0000',
            'subtotal' => '1000.00',
            'vat_amount' => '190.00',
            'total' => '1190.00',
        ]);
    }

    public function test_standalone_quotation_persists_multiple_lines_and_traceability(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Multi línea']);

        $this->actingAs($user)
            ->post('/cotizaciones', [
                '_form' => 'quotation-create',
                'project_id' => $project->id,
                'work_description' => 'Solicitud multi producto',
                'designed_solution' => 'Solución con dos equipos',
                'lines' => [
                    [
                        'product_name' => 'Cámara',
                        'quantity' => '2',
                        'unit_price' => '100',
                    ],
                    [
                        'product_name' => 'Cable',
                        'quantity' => '10',
                        'unit_price' => '5',
                    ],
                ],
            ])
            ->assertRedirect(route('cotizaciones'));

        $quotation = Quotation::query()->firstOrFail();
        $this->assertSame('Solución con dos equipos', $quotation->designed_solution);
        $this->assertSame(2, QuotationLine::query()->where('quotation_id', $quotation->id)->count());
        $this->assertDatabaseHas('traceability_events_tb', [
            'project_id' => $project->id,
            'quotation_id' => $quotation->id,
            'event_type' => 'quotation.created',
        ]);
        $this->assertDatabaseHas('audit_logs_tb', [
            'auditable_id' => $quotation->id,
            'action' => 'quotation.created',
        ]);
    }

    public function test_standalone_validation_failure_reopens_create_modal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from('/cotizaciones')
            ->post('/cotizaciones', [
                '_form' => 'quotation-create',
                'work_description' => 'Sin proyecto',
                'lines' => [[
                    'product_name' => 'Cámara',
                    'quantity' => '1',
                    'unit_price' => '100',
                ]],
            ])
            ->assertRedirect(route('cotizaciones', ['crear' => 1]))
            ->assertSessionHasErrors('project_id');

        $this->actingAs($user)
            ->get('/cotizaciones?crear=1')
            ->assertOk()
            ->assertSee('Selecciona un proyecto');
    }
}
