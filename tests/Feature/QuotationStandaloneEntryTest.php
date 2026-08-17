<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Project;
use App\Models\Quotation;
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
        ]);
        $this->assertSame(1, Quotation::query()->count());
    }
}
