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
            ->assertSee('Planos')
            ->assertSee('Abrir planos')
            ->assertSee('Este proyecto aún no tiene planos.');
    }

    public function test_floor_plan_tab_query_is_accepted(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Obra Centro']);

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'?tab=planos')
            ->assertOk()
            ->assertSee('Planos')
            ->assertSee('data-initial-tab="planos"', false);
    }

    public function test_legacy_cctv_tab_query_maps_to_floor_plan(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Obra Legacy']);

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'?tab=cctv')
            ->assertOk()
            ->assertSee('Planos')
            ->assertSee('data-initial-tab="planos"', false);
    }

    public function test_floor_plan_sheet_can_be_stored_updated_and_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Torre Sur']);

        $this->actingAs($user)
            ->post(route('projects.floor-plans.store', $project), [
                'floor_plans' => [$this->fakePngUpload('piso-1.png')],
                'floor_plan_names' => ['Piso 1'],
                'floor_plan_descriptions' => ['Planta baja'],
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $this->assertDatabaseHas('floor_plans_tb', [
            'project_id' => $project->id,
            'name' => 'Piso 1',
            'description' => 'Planta baja',
            'status' => 'activo',
        ]);

        $floorPlan = FloorPlan::query()->where('project_id', $project->id)->firstOrFail();

        $this->actingAs($user)
            ->put(route('projects.floor-plans.update', [$project, $floorPlan]), [
                'name' => 'Parqueadero',
                'description' => 'Sótano',
                'status' => 'activo',
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $this->assertDatabaseHas('floor_plans_tb', [
            'id' => $floorPlan->id,
            'name' => 'Parqueadero',
        ]);

        $this->actingAs($user)
            ->delete(route('projects.floor-plans.destroy', [$project, $floorPlan]))
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

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

    public function test_camera_can_be_placed_updated_unplaced_and_deleted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Campus');

        $this->actingAs($user)
            ->post(route('projects.cameras.store', $project), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 1,
                'name' => 'Entrada principal',
                'pos_x' => 0.255,
                'pos_y' => 0.4025,
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $camera = ProjectCamera::query()->where('project_id', $project->id)->firstOrFail();
        $this->assertSame('Entrada principal', $camera->name);
        $this->assertEqualsWithDelta(0.255, (float) $camera->pos_x, 0.0001);

        $this->actingAs($user)
            ->put(route('projects.cameras.update', [$project, $camera]), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 2,
                'name' => 'Entrada norte',
                'pos_x' => 0.30,
                'pos_y' => 0.45,
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $this->assertDatabaseHas('cameras_tb', [
            'id' => $camera->id,
            'name' => 'Entrada norte',
            'channel' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('projects.cameras.unplace', [$project, $camera]))
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $camera->refresh();
        $this->assertNull($camera->floor_plan_id);
        $this->assertDatabaseHas('cameras_tb', ['id' => $camera->id, 'name' => 'Entrada norte']);

        $this->actingAs($user)
            ->delete(route('projects.cameras.destroy', [$project, $camera]))
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $this->assertDatabaseMissing('cameras_tb', ['id' => $camera->id]);
    }

    public function test_duplicate_channel_on_same_plan_is_rejected(): void
    {
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Duplicado');

        $project->projectCameras()->create([
            'floor_plan_id' => $floorPlan->id,
            'dvr_id' => $dvr->id,
            'channel' => 1,
            'name' => 'Cam 1',
            'pos_x' => 0.2,
            'pos_y' => 0.2,
        ]);

        $this->actingAs($user)
            ->from(route('projects.show', ['project' => $project, 'tab' => 'planos']))
            ->post(route('projects.cameras.store', $project), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 1,
                'name' => 'Cam 1 bis',
                'pos_x' => 0.5,
                'pos_y' => 0.5,
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']))
            ->assertSessionHasErrors('channel');

        $this->assertSame(1, ProjectCamera::query()->where('project_id', $project->id)->count());
    }

    public function test_unplaced_camera_can_be_located_on_plan(): void
    {
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Inventario');

        $camera = $project->projectCameras()->create([
            'floor_plan_id' => null,
            'dvr_id' => $dvr->id,
            'channel' => 3,
            'name' => 'Bodega',
            'pos_x' => null,
            'pos_y' => null,
        ]);

        $this->actingAs($user)
            ->post(route('projects.cameras.store', $project), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 3,
                'name' => 'Bodega norte',
                'pos_x' => 0.41,
                'pos_y' => 0.62,
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $camera->refresh();
        $this->assertSame($floorPlan->id, $camera->floor_plan_id);
        $this->assertSame('Bodega norte', $camera->name);
        $this->assertSame(1, ProjectCamera::query()->where('project_id', $project->id)->count());
    }

    public function test_camera_position_can_be_patched(): void
    {
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Mover');
        $camera = $project->projectCameras()->create([
            'floor_plan_id' => $floorPlan->id,
            'dvr_id' => $dvr->id,
            'channel' => 1,
            'name' => 'Cam 1',
            'pos_x' => 0.10,
            'pos_y' => 0.10,
        ]);

        $this->actingAs($user)
            ->patchJson(route('projects.cameras.position', [$project, $camera]), [
                'pos_x' => 0.77,
                'pos_y' => 0.12,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $camera->refresh();
        $this->assertEqualsWithDelta(0.77, (float) $camera->pos_x, 0.0001);
        $this->assertEqualsWithDelta(0.12, (float) $camera->pos_y, 0.0001);
    }

    public function test_invalid_coordinates_are_rejected(): void
    {
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Coords');
        $camera = $project->projectCameras()->create([
            'floor_plan_id' => $floorPlan->id,
            'dvr_id' => $dvr->id,
            'channel' => 1,
            'name' => 'Cam 1',
            'pos_x' => 0.10,
            'pos_y' => 0.10,
        ]);

        $this->actingAs($user)
            ->patchJson(route('projects.cameras.position', [$project, $camera]), [
                'pos_x' => 1.4,
                'pos_y' => -0.1,
            ])
            ->assertUnprocessable();
    }

    public function test_project_show_lists_dvr_channels_for_floor_plan(): void
    {
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Canales');
        unset($floorPlan);

        $project->projectCameras()->create([
            'floor_plan_id' => $project->floorPlans()->first()->id,
            'dvr_id' => $dvr->id,
            'channel' => 1,
            'name' => 'Cam 1',
            'pos_x' => 0.2,
            'pos_y' => 0.3,
        ]);

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'?tab=planos')
            ->assertOk()
            ->assertSee('Canal 01')
            ->assertSee('Ya ubicado')
            ->assertSee($dvr->brand);
    }

    public function test_camera_photo_can_be_uploaded_on_place(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Foto');

        $this->actingAs($user)
            ->post(route('projects.cameras.store', $project), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 4,
                'name' => 'Pasillo',
                'pos_x' => 0.33,
                'pos_y' => 0.44,
                'photo' => $this->fakePngUpload('ubicacion.png'),
            ])
            ->assertRedirect(route('projects.show', ['project' => $project, 'tab' => 'planos']));

        $camera = ProjectCamera::query()->where('project_id', $project->id)->firstOrFail();
        $this->assertNotNull($camera->photo_path);
        Storage::disk('public')->assertExists($camera->photo_path);
    }

    public function test_camera_from_another_project_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        [$project, $floorPlan, $dvr] = $this->makePlanContext('Proyecto A');
        $other = Project::createRecord(['name' => 'Proyecto B']);
        $camera = $project->projectCameras()->create([
            'floor_plan_id' => $floorPlan->id,
            'dvr_id' => $dvr->id,
            'channel' => 1,
            'name' => 'Cam 1',
            'pos_x' => 0.10,
            'pos_y' => 0.10,
        ]);

        $this->actingAs($user)
            ->put(route('projects.cameras.update', [$other, $camera]), [
                'floor_plan_id' => $floorPlan->id,
                'dvr_id' => $dvr->id,
                'channel' => 1,
                'name' => 'Hack',
                'pos_x' => 0.10,
                'pos_y' => 0.10,
            ])
            ->assertNotFound();
    }

    /**
     * @return array{0: Project, 1: FloorPlan, 2: \App\Models\Dvr}
     */
    private function makePlanContext(string $name): array
    {
        $project = Project::createRecord(['name' => $name]);
        $floorPlan = $project->floorPlans()->create([
            'path' => 'floor_plans/campus.png',
            'name' => 'Planta baja',
            'sort_order' => 0,
            'status' => 'activo',
        ]);
        $dvr = $project->dvrs()->create([
            'brand' => 'Hikvision',
            'serial_model' => 'DS-7208',
            'ports' => 8,
            'disks' => 1,
        ]);

        return [$project, $floorPlan, $dvr];
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
