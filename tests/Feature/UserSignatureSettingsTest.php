<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Infrastructure\Settings\EloquentCompanyIdentity;
use App\Infrastructure\Settings\EloquentUserSignature;
use App\Models\AppSetting;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserSignatureSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_shows_signature_section_and_two_column_layout(): void
    {
        $user = User::factory()->create();
        AppSetting::query()->create(['key' => 'vat_rate_percent', 'value' => '16.0000']);

        $this->actingAs($user)
            ->get('/configuracion')
            ->assertOk()
            ->assertSee('Datos de la cuenta')
            ->assertSee('Identidad de la empresa')
            ->assertSee('Cambiar contraseña')
            ->assertSee('Configuración comercial')
            ->assertSee('IVA (%)')
            ->assertSee('Firma para cotizaciones')
            ->assertSee('+ Agregar firma')
            ->assertSee('Contraseña actual')
            ->assertSee('Nueva contraseña')
            ->assertSee('Confirmar nueva contraseña')
            ->assertSee('Nombre comercial')
            ->assertSee('Guardar cambios')
            ->assertDontSee('Otras configuraciones')
            ->assertSee('max-w-7xl', false);
    }

    public function test_settings_page_has_only_two_main_cards(): void
    {
        $user = User::factory()->create();
        AppSetting::query()->create(['key' => 'vat_rate_percent', 'value' => '16.0000']);

        $html = $this->actingAs($user)->get('/configuracion')->getContent();
        $this->assertNotFalse($html);
        $this->assertSame(1, substr_count($html, '>Datos de la cuenta</h2>'));
        $this->assertSame(1, substr_count($html, '>Identidad de la empresa</h2>'));
    }

    public function test_user_can_upload_replace_and_delete_signature(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        AppSetting::query()->create(['key' => 'vat_rate_percent', 'value' => '16.0000']);

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post('/configuracion/firma', [
                'signature' => $this->fakePngUpload('firma.png'),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $first = app(EloquentUserSignature::class)->snapshot($user->fresh());
        $this->assertNotNull($first['path']);
        Storage::disk('public')->assertExists($first['path']);

        $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post('/configuracion/firma', [
                'signature' => $this->fakePngUpload('firma-2.png'),
            ])
            ->assertOk();

        $second = app(EloquentUserSignature::class)->snapshot($user->fresh());
        $this->assertNotSame($first['path'], $second['path']);
        Storage::disk('public')->assertMissing($first['path']);

        $this->actingAs($user)
            ->deleteJson('/configuracion/firma')
            ->assertOk();

        $this->assertNull($user->fresh()->signature_path);
    }

    public function test_user_can_store_drawn_signature_from_base64(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        $this->actingAs($user)
            ->postJson('/configuracion/firma', [
                'signature_data' => 'data:image/png;base64,'.base64_encode($png),
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertNotNull($user->fresh()->signature_path);
    }

    public function test_quotation_pdf_keeps_historical_signature_snapshot(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['name' => 'Omar Mira', 'phone' => '3006033638']);

        $this->actingAs($user)->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])->post('/configuracion/firma', [
            'signature' => $this->fakePngUpload('firma-v1.png'),
        ])->assertOk();

        $project = Project::createRecord(['name' => 'Torre Norte']);
        $quotation = Quotation::query()->create([
            'project_id' => $project->id,
            'code' => 'COT-SIG-1',
            'work_description' => 'CCTV',
            'status' => 'borrador',
            'vat_rate_percent' => '16.0000',
            'subtotal' => 100,
            'vat_amount' => 16,
            'total' => 116,
            'created_by' => $user->id,
        ]);

        $snapshotService = app(\App\Application\Quotation\QuotationSignatureSnapshot::class);
        $snapshotService->resolveForPdf($quotation, $user);

        $firstSnapshot = $quotation->fresh()->signature_snapshot_path;
        $this->assertNotNull($firstSnapshot);
        $this->assertSame('3006033638', $quotation->fresh()->signatory_phone);
        Storage::disk('public')->assertExists($firstSnapshot);

        $user->update(['phone' => '3100000000', 'name' => 'Otro Nombre']);
        $this->actingAs($user->fresh())->deleteJson('/configuracion/firma')->assertOk();
        $this->actingAs($user)->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])->post('/configuracion/firma', [
            'signature' => $this->fakePngUpload('firma-v2.png'),
        ])->assertOk();

        $snapshotService->resolveForPdf($quotation->fresh(), $user->fresh());

        $this->assertSame($firstSnapshot, $quotation->fresh()->signature_snapshot_path);
        $this->assertSame('3006033638', $quotation->fresh()->signatory_phone);
        $this->assertSame('Omar Mira', $quotation->fresh()->signatory_name);
    }

    public function test_signature_appears_in_quotation_pdf_html(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['name' => 'Omar Mira', 'phone' => '3006033638']);

        $this->actingAs($user)->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])->post('/configuracion/firma', [
            'signature' => $this->fakePngUpload('firma.png'),
        ]);

        $project = Project::createRecord(['name' => 'Obra Centro']);
        $quotation = Quotation::query()->create([
            'project_id' => $project->id,
            'code' => 'COT-SIG-2',
            'work_description' => 'CCTV accesos',
            'designed_solution' => '4 cámaras',
            'status' => 'borrador',
            'vat_rate_percent' => '16.0000',
            'subtotal' => 100,
            'vat_amount' => 16,
            'total' => 116,
            'created_by' => $user->id,
        ]);

        $entity = app(QuotationRepositoryInterface::class)->findById(QuotationId::fromInt((int) $quotation->id));
        $this->assertNotNull($entity);

        $identity = app(EloquentCompanyIdentity::class);
        $signature = app(EloquentUserSignature::class);

        $html = view('pdf.quotations.show', [
            'quotation' => $entity,
            'projectName' => $project->name,
            'lines' => $entity->lines(),
            'company' => $identity->snapshot(),
            'logoDataUri' => null,
            'signatureDataUri' => $signature->dataUriFromPath($user->fresh()->signature_path),
            'signatoryName' => $user->name,
            'signatoryPhone' => $user->phone,
        ])->render();

        $this->assertStringContainsString('Atentamente,', $html);
        $this->assertStringContainsString('Omar Mira', $html);
        $this->assertStringContainsString('Celular: 3006033638', $html);
        $this->assertStringContainsString('signature-image', $html);
    }

    public function test_quotation_pdf_shows_sender_without_signature_image(): void
    {
        $user = User::factory()->create(['name' => 'Omar Mira', 'phone' => '3006033638']);

        $project = Project::createRecord(['name' => 'Obra Centro']);
        $quotation = Quotation::query()->create([
            'project_id' => $project->id,
            'code' => 'COT-SIG-3',
            'work_description' => 'CCTV accesos',
            'status' => 'borrador',
            'vat_rate_percent' => '16.0000',
            'subtotal' => 100,
            'vat_amount' => 16,
            'total' => 116,
            'created_by' => $user->id,
        ]);

        $entity = app(QuotationRepositoryInterface::class)->findById(QuotationId::fromInt((int) $quotation->id));
        $this->assertNotNull($entity);

        $block = app(\App\Application\Quotation\QuotationSignatureSnapshot::class)->resolveForPdf($quotation, $user);

        $html = view('pdf.quotations.show', [
            'quotation' => $entity,
            'projectName' => $project->name,
            'lines' => $entity->lines(),
            'company' => app(EloquentCompanyIdentity::class)->snapshot(),
            'logoDataUri' => null,
            'signatureDataUri' => $block['signatureDataUri'],
            'signatoryName' => $block['signatoryName'],
            'signatoryPhone' => $block['signatoryPhone'],
        ])->render();

        $this->assertStringContainsString('Atentamente,', $html);
        $this->assertStringContainsString('Omar Mira', $html);
        $this->assertStringContainsString('Celular: 3006033638', $html);
        $this->assertStringNotContainsString('<img class="signature-image"', $html);
    }

    public function test_settings_page_shows_user_phone_field(): void
    {
        $user = User::factory()->create(['phone' => '3006033638']);
        AppSetting::query()->create(['key' => 'vat_rate_percent', 'value' => '16.0000']);

        $this->actingAs($user)
            ->get('/configuracion')
            ->assertOk()
            ->assertSee('Celular')
            ->assertSee('3006033638');
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
