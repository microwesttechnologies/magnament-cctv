<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ServiceOrder;
use App\Models\Staff;
use App\Models\TechnicianNotification;
use App\Models\TraceabilityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceOrderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_can_create_and_assign_a_service_order(): void
    {
        [$admin, $project, $darwin] = $this->seedOfficeContext();

        $this->actingAs($admin)
            ->post('/ordenes', [
                'project_id' => $project->id,
                'description' => 'Mantenimiento DVR principal',
                'priority' => 'alta',
                'staff_id' => $darwin->id,
                'observations' => 'Revisar discos',
            ])
            ->assertRedirect();

        $order = ServiceOrder::query()->firstOrFail();
        $this->assertSame('asignada', $order->status);
        $this->assertSame((int) $darwin->id, (int) $order->staff_id);
        $this->assertStringStartsWith('OS-', $order->code);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $order->id,
            'event_type' => 'service_order.created',
        ]);
        $this->assertDatabaseHas('technician_notifications_tb', [
            'user_id' => $darwin->user_id,
            'service_order_id' => $order->id,
            'type' => 'assigned',
        ]);
    }

    public function test_office_can_assign_a_pending_order(): void
    {
        [$admin, $project, $darwin] = $this->seedOfficeContext();

        $this->actingAs($admin)
            ->post('/ordenes', [
                'project_id' => $project->id,
                'description' => 'Revisión cámara 08',
                'priority' => 'media',
            ])
            ->assertRedirect();

        $order = ServiceOrder::query()->firstOrFail();
        $this->assertSame('pendiente', $order->status);

        $this->actingAs($admin)
            ->post('/ordenes/'.$order->id.'/asignar', [
                'staff_id' => $darwin->id,
            ])
            ->assertRedirect();

        $this->assertSame('asignada', $order->fresh()->status);
        $this->assertSame((int) $darwin->id, (int) $order->fresh()->staff_id);
    }

    public function test_office_can_reassign_and_previous_technician_loses_access(): void
    {
        [$admin, $project, $darwin, $carlos, $order] = $this->seedAssignedOrder();

        $this->actingAs($admin)
            ->post('/ordenes/'.$order->id.'/reasignar', [
                'staff_id' => $carlos->id,
                'reason' => 'Cambio de zona',
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame((int) $carlos->id, (int) $order->staff_id);
        $this->assertSame('asignada', $order->status);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $order->id,
            'event_type' => 'service_order.reassigned',
        ]);

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->get('/tecnico/ordenes/'.$order->id)
            ->assertForbidden();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($carlos->user)
            ->get('/tecnico/ordenes/'.$order->id)
            ->assertOk()
            ->assertSee($order->code);
    }

    public function test_technician_login_with_email_and_document_and_sees_only_own_orders(): void
    {
        [, $project, $darwin, $carlos] = $this->seedAssignedOrder();

        $foreign = ServiceOrder::query()->create([
            'code' => 'OS-2026-0099',
            'project_id' => $project->id,
            'staff_id' => $carlos->id,
            'description' => 'Orden ajena',
            'priority' => 'media',
            'status' => 'asignada',
            'assigned_at' => now(),
        ]);

        $this->withHeaders($this->mobileHeaders())
            ->post('/tecnico/login', [
                'email' => $darwin->email,
                'document_number' => $darwin->document_number,
            ])
            ->assertRedirect(route('technician.home'));

        $this->withHeaders($this->mobileHeaders())
            ->get('/tecnico/ordenes')
            ->assertOk()
            ->assertSee('OS-')
            ->assertDontSee($foreign->code);
    }

    public function test_technician_cannot_access_office_panel_or_foreign_orders(): void
    {
        [, , $darwin, $carlos, $order] = $this->seedAssignedOrder();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->get('/dashboard')
            ->assertRedirect(route('technician.home'));

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->get('/cotizaciones')
            ->assertRedirect(route('technician.home'));

        $foreign = ServiceOrder::query()->create([
            'code' => 'OS-2026-0088',
            'project_id' => $order->project_id,
            'staff_id' => $carlos->id,
            'description' => 'Cámara 08',
            'priority' => 'media',
            'status' => 'asignada',
            'assigned_at' => now(),
        ]);

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->get('/tecnico/ordenes/'.$foreign->id)
            ->assertForbidden();
    }

    public function test_desktop_technician_is_blocked_from_pwa_workspace(): void
    {
        [, , $darwin] = $this->seedAssignedOrder();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ])
            ->actingAs($darwin->user)
            ->get('/tecnico')
            ->assertForbidden()
            ->assertSee('Esta aplicación está diseñada para técnicos desde dispositivos móviles.');
    }

    public function test_technician_can_start_order_and_cannot_resolve_without_png(): void
    {
        Storage::fake('public');
        [, , $darwin, , $order] = $this->seedAssignedOrder();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/tecnico/ordenes/'.$order->id.'/iniciar')
            ->assertRedirect();

        $this->assertSame('en_proceso', $order->fresh()->status);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $order->id,
            'event_type' => 'service_order.started',
        ]);

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/tecnico/ordenes/'.$order->id.'/resolver', [
                'resolution_notes' => 'Listo',
            ])
            ->assertSessionHasErrors('resolution_notes');

        $this->assertSame('en_proceso', $order->fresh()->status);
    }

    public function test_technician_can_resolve_and_cancel_only_with_png_evidence(): void
    {
        Storage::fake('public');
        [, $project, $darwin] = $this->seedOfficeContext();

        $resolve = $this->makeAssignedOrder($project, $darwin, 'OS-2026-0101');
        $cancel = $this->makeAssignedOrder($project, $darwin, 'OS-2026-0102');

        $this->withHeaders($this->mobileHeaders())->actingAs($darwin->user);
        $this->post('/tecnico/ordenes/'.$resolve->id.'/iniciar')->assertRedirect();
        $this->post('/tecnico/ordenes/'.$cancel->id.'/iniciar')->assertRedirect();

        $this->post('/tecnico/ordenes/'.$cancel->id.'/cancelar', [
            'cancellation_reason' => 'Sin PNG aún',
        ])->assertSessionHasErrors('cancellation_reason');
        $this->assertSame('en_proceso', $cancel->fresh()->status);

        $this->post('/tecnico/ordenes/'.$resolve->id.'/evidencia', [
            'evidence' => $this->pngUpload('cierre.png'),
            'description' => 'Foto del DVR',
        ])->assertRedirect();

        $this->post('/tecnico/ordenes/'.$resolve->id.'/resolver', [
            'resolution_notes' => 'DVR operativo',
        ])->assertRedirect();
        $this->assertSame('resuelta', $resolve->fresh()->status);

        $this->post('/tecnico/ordenes/'.$cancel->id.'/evidencia', [
            'evidence' => $this->pngUpload('cancel.png'),
        ])->assertRedirect();
        $this->post('/tecnico/ordenes/'.$cancel->id.'/cancelar', [
            'cancellation_reason' => 'El equipo no pudo ser reemplazado porque el repuesto no se encuentra disponible.',
        ])->assertRedirect();
        $this->assertSame('cancelada', $cancel->fresh()->status);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $resolve->id,
            'event_type' => 'service_order.resolved',
        ]);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $cancel->id,
            'event_type' => 'service_order.cancelled',
        ]);
    }

    public function test_evidence_rejects_non_png_files(): void
    {
        Storage::fake('public');
        [, , $darwin, , $order] = $this->seedAssignedOrder();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/tecnico/ordenes/'.$order->id.'/evidencia', [
                'evidence' => UploadedFile::fake()->createWithContent('nota.txt', 'no es imagen'),
            ])
            ->assertSessionHasErrors('evidence');
    }

    public function test_invalid_status_transitions_are_blocked(): void
    {
        [, , $darwin, , $order] = $this->seedAssignedOrder();
        $order->update(['status' => 'pendiente', 'staff_id' => $darwin->id]);

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/tecnico/ordenes/'.$order->id.'/iniciar')
            ->assertForbidden();

        $fresh = $order->fresh();
        $this->assertNotNull($fresh);
        $this->assertFalse($fresh->statusEnum()->canStart());

        try {
            $fresh->start();
            $this->fail('La transición inválida debió rechazarse.');
        } catch (\App\Domain\ServiceOrder\Exceptions\InvalidServiceOrderTransition $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    public function test_pwa_manifest_and_service_worker_are_public(): void
    {
        $this->get('/manifest-tecnico.webmanifest')->assertOk();
        $this->get('/tecnico/sw.js')->assertOk();

        $manifest = (string) file_get_contents(public_path('manifest-tecnico.webmanifest'));
        $this->assertStringContainsString('standalone', $manifest);
        $this->assertStringContainsString('/tecnico', $manifest);

        $sw = (string) file_get_contents(public_path('tecnico/sw.js'));
        $this->assertStringContainsString('push', $sw);
        $this->assertStringContainsString('notificationclick', $sw);
    }

    public function test_technician_can_subscribe_push_endpoint(): void
    {
        [, , $darwin] = $this->seedAssignedOrder();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->postJson('/tecnico/push/subscribe', [
                'endpoint' => 'https://push.example/sub-1',
                'keys' => [
                    'p256dh' => str_repeat('A', 40),
                    'auth' => str_repeat('B', 20),
                ],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('push_subscriptions_tb', [
            'user_id' => $darwin->user_id,
            'endpoint' => 'https://push.example/sub-1',
        ]);
    }

    public function test_office_guest_cannot_open_technician_area(): void
    {
        $this->get('/tecnico')->assertRedirect(route('technician.login'));
        $this->actingAs(User::factory()->create())
            ->withHeaders($this->mobileHeaders())
            ->get('/tecnico')
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Project, 2: Staff}
     */
    private function seedOfficeContext(): array
    {
        $admin = User::factory()->create(['name' => 'Supervisor', 'role' => 'user']);
        $project = Project::createRecord([
            'name' => 'Torres de Hungría 6',
            'address' => 'Calle 1',
            'city' => 'Bogotá',
        ]);
        $darwin = $this->makeTechnician('Darwin Smith', 'darwin@cctv.test', '1001');

        return [$admin, $project, $darwin];
    }

    /**
     * @return array{0: User, 1: Project, 2: Staff, 3: Staff, 4: ServiceOrder}
     */
    private function seedAssignedOrder(): array
    {
        [$admin, $project, $darwin] = $this->seedOfficeContext();
        $carlos = $this->makeTechnician('Carlos Pérez', 'carlos@cctv.test', '1002');
        $order = $this->makeAssignedOrder($project, $darwin, 'OS-2026-0001');

        return [$admin, $project, $darwin, $carlos, $order];
    }

    private function makeTechnician(string $name, string $email, string $document): Staff
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => 'tecnico',
        ]);

        return Staff::query()->create([
            'user_id' => $user->id,
            'name' => $name,
            'email' => $email,
            'document_number' => $document,
            'role' => 'tecnico',
            'status' => 'activo',
        ]);
    }

    private function makeAssignedOrder(Project $project, Staff $technician, string $code): ServiceOrder
    {
        return ServiceOrder::query()->create([
            'code' => $code,
            'project_id' => $project->id,
            'staff_id' => $technician->id,
            'description' => 'Revisión de DVR principal',
            'priority' => 'alta',
            'status' => 'asignada',
            'assigned_at' => now(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function mobileHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ];
    }

    private function pngUpload(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
