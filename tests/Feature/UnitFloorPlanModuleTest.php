<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FloorPlan;
use App\Models\Project;
use App\Models\ProjectCamera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UnitFloorPlanModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_away_from_project_floor_plan(): void
    {
        $project = Project::createRecord(['name' => 'Residencial Norte']);

        $this->get('/projects/'.$project->id)
            ->assertRedirect('/login');
    }

    public function test_project_show_includes_unit_floor_plan_module(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Residencial Norte']);

        $this->actingAs($user)
            ->get('/projects/'.$project->id)
            ->assertOk()
            ->assertSee('Plano de la Unidad')
            ->assertSee('Abrir plano');
    }

    public function test_floor_plan_tab_query_is_accepted(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Obra Centro']);

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'?tab=plano')
            ->assertOk()
            ->assertSee('Plano de la Unidad')
            ->assertSee('data-initial-tab="plano"', false);
    }

    public function test_legacy_cctv_tab_query_maps_to_floor_plan(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Obra Legacy']);

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'?tab=cctv')
            ->assertOk()
            ->assertSee('Plano de la Unidad')
            ->assertSee('data-initial-tab="plano"', false);
    }

    public function test_floor_plan_sheet_can_be_stored_and_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Torre Sur']);

        $this->actingAs($user)
            ->post(route('projects.floor-plans.store', $project), [
                'floor_plans' => [$this->fakePngUpload('piso-1.png')],
                'floor_plan_names' => ['Piso 1'],
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'plano']));

        $this->assertDatabaseHas('floor_plans_tb', [
            'project_id' => $project->id,
            'name' => 'Piso 1',
        ]);

        $floorPlan = FloorPlan::query()->where('project_id', $project->id)->firstOrFail();

        $this->actingAs($user)
            ->delete(route('projects.floor-plans.destroy', [$project, $floorPlan]))
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'plano']));

        $this->assertDatabaseMissing('floor_plans_tb', ['id' => $floorPlan->id]);
    }

    public function test_floor_plan_from_another_project_cannot_be_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Proyecto A']);
        $other = Project::createRecord(['name' => 'Proyecto B']);
        $floorPlan = $project->floorPlans()->create([
            'path' => 'floor_plans/a.png',
            'name' => 'Hoja A',
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.floor-plans.destroy', [$other, $floorPlan]))
            ->assertNotFound();

        $this->assertDatabaseHas('floor_plans_tb', ['id' => $floorPlan->id]);
    }

    public function test_camera_can_be_placed_updated_and_deleted_on_floor_plan(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Campus']);
        $floorPlan = $project->floorPlans()->create([
            'path' => 'floor_plans/campus.png',
            'name' => 'Planta baja',
            'sort_order' => 0,
        ]);
        $dvr = $project->dvrs()->create([
            'brand' => 'Hikvision',
            'serial_model' => 'DS-7208',
            'ports' => 8,
            'disks' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('projects.cameras.store', $project), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 1,
                'name' => 'Entrada principal',
                'pos_x' => 25.5,
                'pos_y' => 40.25,
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'plano']));

        $camera = ProjectCamera::query()->where('project_id', $project->id)->firstOrFail();
        $this->assertSame('Entrada principal', $camera->name);

        $this->actingAs($user)
            ->put(route('projects.cameras.update', [$project, $camera]), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 2,
                'name' => 'Entrada norte',
                'pos_x' => 30,
                'pos_y' => 45,
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'plano']));

        $this->assertDatabaseHas('cameras_tb', [
            'id' => $camera->id,
            'name' => 'Entrada norte',
            'channel' => 2,
        ]);

        $this->actingAs($user)
            ->delete(route('projects.cameras.destroy', [$project, $camera]))
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'plano']));

        $this->assertDatabaseMissing('cameras_tb', ['id' => $camera->id]);
    }

    public function test_camera_from_another_project_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Proyecto A']);
        $other = Project::createRecord(['name' => 'Proyecto B']);
        $floorPlan = $project->floorPlans()->create([
            'path' => 'floor_plans/a.png',
            'name' => 'Hoja A',
            'sort_order' => 0,
        ]);
        $dvr = $project->dvrs()->create([
            'brand' => 'Dahua',
            'serial_model' => 'XVR',
            'ports' => 4,
            'disks' => 1,
        ]);
        $camera = $project->projectCameras()->create([
            'floor_plan_id' => $floorPlan->id,
            'dvr_id' => $dvr->id,
            'channel' => 1,
            'name' => 'Cam 1',
            'pos_x' => 10,
            'pos_y' => 10,
        ]);

        $this->actingAs($user)
            ->put(route('projects.cameras.update', [$other, $camera]), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 1,
                'name' => 'Hack',
                'pos_x' => 10,
                'pos_y' => 10,
            ])
            ->assertNotFound();
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
