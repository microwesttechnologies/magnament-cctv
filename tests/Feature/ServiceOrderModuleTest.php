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

    public function test_technician_can_login_from_office_form_with_email_and_cedula(): void
    {
        [, , $darwin] = $this->seedOfficeContext();

        $this->post('/login', [
            'email' => $darwin->email,
            'password' => $darwin->document_number,
        ])->assertRedirect(route('technician.home'));

        $this->assertAuthenticatedAs($darwin->user);
    }

    public function test_technician_login_accepts_formatted_document_and_email_case(): void
    {
        $admin = User::factory()->create(['role' => 'user']);
        $staff = Staff::query()->create([
            'name' => 'Ana Técnico',
            'email' => 'ana@cctv.test',
            'document_number' => '1.234.567',
            'role' => 'tecnico',
            'status' => 'activo',
        ]);

        $this->assertNull($staff->user_id);
        $this->assertTrue($admin->isOffice());

        $this->post('/tecnico/login', [
            'email' => 'Ana@cctv.test',
            'document_number' => '1234567',
        ])->assertRedirect(route('technician.home'));

        $this->assertNotNull($staff->fresh()->user_id);
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isTechnician());
    }

    public function test_office_login_still_requires_password_for_admin(): void
    {
        $admin = User::factory()->create([
            'email' => 'jefe@cctv.test',
            'password' => 'secret-office',
            'role' => 'user',
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'secret-office',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
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

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0',
        ])
            ->actingAs($darwin->user)
            ->get('/tecnico/?source=pwa')
            ->assertOk();
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
            ->post('/tecnico/ordenes/'.$order->id.'/finalizar', [
                'result' => 'resuelta',
                'observation' => 'Listo',
            ])
            ->assertSessionHasErrors('observation');

        $this->assertSame('en_proceso', $order->fresh()->status);
    }

    public function test_technician_unified_completion_workflow(): void
    {
        Storage::fake('public');
        [, $project, $darwin] = $this->seedOfficeContext();

        $order = $this->makeAssignedOrder($project, $darwin, 'OS-2026-0200');

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->get('/tecnico/ordenes/'.$order->id)
            ->assertOk()
            ->assertSee('Iniciar orden')
            ->assertDontSee('Resolver orden')
            ->assertDontSee('Cancelar orden')
            ->assertDontSee('Finalizar orden');

        $this->post('/tecnico/ordenes/'.$order->id.'/iniciar')->assertRedirect();
        $this->assertSame('en_proceso', $order->fresh()->status);

        $this->get('/tecnico/ordenes/'.$order->id)
            ->assertOk()
            ->assertSee('Resultado de la orden')
            ->assertSee('¿Cuál fue el resultado?')
            ->assertSee('Seleccionar resultado...')
            ->assertSee('No resuelta')
            ->assertDontSee('Resolver orden')
            ->assertDontSee('Cancelar orden');

        $this->post('/tecnico/ordenes/'.$order->id.'/finalizar', [
            'result' => 'resuelta',
            'observation' => '',
        ])->assertSessionHasErrors('observation');

        $this->post('/tecnico/ordenes/'.$order->id.'/finalizar', [
            'result' => 'resuelta',
            'observation' => 'Sin evidencia aún',
        ])->assertSessionHasErrors('observation');

        $this->post('/tecnico/ordenes/'.$order->id.'/evidencia', [
            'evidence' => $this->pngUpload('visita.png'),
        ])->assertRedirect();

        $this->post('/tecnico/ordenes/'.$order->id.'/finalizar', [
            'result' => 'resuelta',
            'observation' => 'Equipo operativo',
        ])->assertRedirect();

        $this->assertSame('resuelta', $order->fresh()->status);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $order->id,
            'event_type' => 'service_order.resolved',
        ]);
    }

    public function test_technician_can_mark_order_as_unresolved_with_evidence(): void
    {
        Storage::fake('public');
        [, $project, $darwin] = $this->seedOfficeContext();
        $order = $this->makeAssignedOrder($project, $darwin, 'OS-2026-0201');

        $this->withHeaders($this->mobileHeaders())->actingAs($darwin->user);
        $this->post('/tecnico/ordenes/'.$order->id.'/iniciar')->assertRedirect();
        $this->post('/tecnico/ordenes/'.$order->id.'/evidencia', [
            'evidence' => $this->pngUpload('no-resuelta.png'),
        ])->assertRedirect();

        $this->post('/tecnico/ordenes/'.$order->id.'/finalizar', [
            'result' => 'no_resuelta',
            'observation' => 'Se revisó el DVR pero el repuesto no está disponible.',
        ])->assertRedirect();

        $fresh = $order->fresh();
        $this->assertSame('no_resuelta', $fresh->status);
        $this->assertSame('Se revisó el DVR pero el repuesto no está disponible.', $fresh->unresolved_notes);
        $this->assertDatabaseHas('traceability_events_tb', [
            'service_order_id' => $order->id,
            'event_type' => 'service_order.unresolved',
        ]);
    }

    public function test_technician_cannot_finalize_foreign_order(): void
    {
        Storage::fake('public');
        [, $project, $darwin, $carlos] = $this->seedAssignedOrder();
        $foreign = ServiceOrder::query()->create([
            'code' => 'OS-2026-0202',
            'project_id' => $project->id,
            'staff_id' => $carlos->id,
            'description' => 'Orden ajena',
            'priority' => 'media',
            'status' => 'en_proceso',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/tecnico/ordenes/'.$foreign->id.'/finalizar', [
                'result' => 'resuelta',
                'observation' => 'Intento ajeno',
            ])
            ->assertForbidden();
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

        $this->post('/tecnico/ordenes/'.$cancel->id.'/finalizar', [
            'result' => 'cancelada',
            'observation' => 'Sin PNG aún',
        ])->assertSessionHasErrors('observation');
        $this->assertSame('en_proceso', $cancel->fresh()->status);

        $this->post('/tecnico/ordenes/'.$resolve->id.'/evidencia', [
            'evidence' => $this->pngUpload('cierre.png'),
            'description' => 'Foto del DVR',
        ])->assertRedirect();

        $this->post('/tecnico/ordenes/'.$resolve->id.'/finalizar', [
            'result' => 'resuelta',
            'observation' => 'DVR operativo',
        ])->assertRedirect();
        $this->assertSame('resuelta', $resolve->fresh()->status);

        $this->post('/tecnico/ordenes/'.$cancel->id.'/evidencia', [
            'evidence' => $this->pngUpload('cancel.png'),
        ])->assertRedirect();
        $this->post('/tecnico/ordenes/'.$cancel->id.'/finalizar', [
            'result' => 'cancelada',
            'observation' => 'El equipo no pudo ser reemplazado porque el repuesto no se encuentra disponible.',
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
        $manifest = $this->get('/manifest.webmanifest');
        $manifest->assertOk();
        $this->assertStringContainsString(
            'application/manifest+json',
            (string) $manifest->headers->get('Content-Type'),
        );
        $manifest->assertJsonPath('start_url', '/?source=pwa');
        $manifest->assertJsonPath('scope', '/');
        $manifest->assertJsonPath('id', '/');
        $manifest->assertJsonPath('display', 'standalone');
        $this->get('/manifest-tecnico.webmanifest')->assertOk();

        $sw = $this->get('/sw.js');
        $sw->assertOk();
        $this->assertSame('/', $sw->headers->get('Service-Worker-Allowed'));
        $this->assertStringContainsString('notificationclick', (string) file_get_contents(public_path('sw.js')));

        $this->get('/tecnico/sw.js')->assertOk();
        $this->get('/offline.html')->assertOk();
        $this->get('/tecnico/offline.html')->assertOk();
        $this->assertFileExists(public_path('images/pwa/icon-192.png'));
        $this->assertFileExists(public_path('images/pwa/icon-512.png'));
        $this->assertFileDoesNotExist(public_path('tecnico/sw.js'));
    }

    public function test_entire_app_exposes_installable_manifest(): void
    {
        $admin = User::factory()->create(['role' => 'user']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('/sw.js', false);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('/sw.js', false);

        $this->actingAs($admin)
            ->get('/personal')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('/sw.js', false);
    }

    public function test_logged_in_technician_is_redirected_from_login_to_technician_home(): void
    {
        [, , $darwin] = $this->seedOfficeContext();

        $this->actingAs($darwin->user)
            ->get('/tecnico/login')
            ->assertRedirect(route('technician.home'));
    }

    public function test_guest_keeps_pwa_source_when_redirected_to_technician_login(): void
    {
        $this->get('/tecnico?source=pwa')
            ->assertRedirect(route('technician.login', ['source' => 'pwa']));
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

    public function test_assigning_an_order_dispatches_web_push_payload(): void
    {
        $dispatcher = new \App\Infrastructure\Notifications\ArrayWebPushDispatcher();
        $this->app->instance(\App\Domain\ServiceOrder\Ports\WebPushDispatcherInterface::class, $dispatcher);

        [$admin, $project, $darwin] = $this->seedOfficeContext();

        $this->actingAs($admin)
            ->post('/ordenes', [
                'project_id' => $project->id,
                'description' => 'Mantenimiento DVR principal',
                'priority' => 'alta',
                'staff_id' => $darwin->id,
            ])
            ->assertRedirect();

        $this->assertNotEmpty($dispatcher->sent);
        $this->assertSame((int) $darwin->user_id, $dispatcher->sent[0]['user_id']);
        $this->assertSame('Nuevo trabajo asignado', $dispatcher->sent[0]['payload']['title']);
    }

    public function test_technician_inbox_marks_notifications_as_read(): void
    {
        [, , $darwin, , $order] = $this->seedAssignedOrder();
        $notification = TechnicianNotification::query()->create([
            'user_id' => $darwin->user_id,
            'service_order_id' => $order->id,
            'type' => 'assigned',
            'title' => 'Nuevo trabajo asignado',
            'body' => $order->code,
            'url' => route('technician.orders.show', $order),
        ]);

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->get('/tecnico/notificaciones')
            ->assertOk()
            ->assertSee('Nuevo trabajo asignado');

        $this->assertNotNull($notification->fresh()?->read_at);
    }

    public function test_technician_cannot_reassign_or_open_office_orders_panel(): void
    {
        [, , $darwin, $carlos, $order] = $this->seedAssignedOrder();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/ordenes/'.$order->id.'/reasignar', [
                'staff_id' => $carlos->id,
            ])
            ->assertRedirect(route('technician.home'));

        $this->assertSame((int) $darwin->id, (int) $order->fresh()->staff_id);
    }

    public function test_stored_evidence_persists_detected_png_mime(): void
    {
        Storage::fake('public');
        [, , $darwin, , $order] = $this->seedAssignedOrder();

        $this->withHeaders($this->mobileHeaders())
            ->actingAs($darwin->user)
            ->post('/tecnico/ordenes/'.$order->id.'/evidencia', [
                'evidence' => $this->pngUpload('cierre.png'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('service_order_evidences_tb', [
            'service_order_id' => $order->id,
            'mime' => 'image/png',
        ]);
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
