<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectContactFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_store_persists_admin_contact_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/projects', [
                '_form' => 'project-create',
                'name' => 'Condominio Prueba',
                'type' => 'Residencial',
                'address' => 'Calle 10',
                'city' => 'Medellín',
                'admin_name' => 'Laura Admin',
                'admin_phone' => '6041234567',
                'admin_email' => 'laura@example.com',
                'action' => 'final',
            ])
            ->assertRedirect(route('projects'));

        $this->assertDatabaseHas('projects_tb', [
            'name' => 'Condominio Prueba',
            'admin_name' => 'Laura Admin',
            'admin_phone' => '6041234567',
            'admin_email' => 'laura@example.com',
        ]);
    }

    public function test_project_update_persists_admin_contact_fields(): void
    {
        $user = User::factory()->create();
        $project = Project::createRecord([
            'name' => 'Proyecto Base',
            'admin_name' => 'Contacto Inicial',
        ]);

        $this->actingAs($user)
            ->put('/projects/'.$project->id, [
                '_form' => 'project-edit',
                'name' => 'Proyecto Base',
                'type' => 'Comercial',
                'address' => 'Av. 80',
                'neighborhood' => 'Centro',
                'city' => 'Cali',
                'admin_name' => 'Nuevo Contacto',
                'admin_phone' => '3009998877',
                'admin_email' => 'contacto@obra.test',
            ])
            ->assertRedirect(route('projects'));

        $this->assertDatabaseHas('projects_tb', [
            'id' => $project->id,
            'admin_name' => 'Nuevo Contacto',
            'admin_phone' => '3009998877',
            'admin_email' => 'contacto@obra.test',
            'type' => 'Comercial',
        ]);
    }
}
