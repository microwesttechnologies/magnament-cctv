<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Quotation\Repositories\QuotationRepositoryInterface;
use App\Domain\Quotation\ValueObjects\QuotationId;
use App\Infrastructure\Settings\EloquentCompanyIdentity;
use App\Models\AppSetting;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationProposalContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_can_be_created_with_solicitud(): void
    {
        [$user, $project] = $this->seedUserAndProject();

        $this->actingAs($user)
            ->post('/cotizaciones', $this->payload($project, [
                'work_description' => 'Cobertura CCTV en accesos',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('quotations_tb', [
            'project_id' => $project->id,
            'work_description' => 'Cobertura CCTV en accesos',
        ]);
    }

    public function test_quotation_can_be_created_with_designed_solution(): void
    {
        [$user, $project] = $this->seedUserAndProject();

        $this->actingAs($user)
            ->post('/cotizaciones', $this->payload($project, [
                'designed_solution' => '4 cámaras IP y DVR 32CH',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('quotations_tb', [
            'project_id' => $project->id,
            'designed_solution' => '4 cámaras IP y DVR 32CH',
        ]);
    }

    public function test_solicitud_can_be_edited(): void
    {
        [$user, $project, $quotation] = $this->createStoredQuotation();

        $this->actingAs($user)
            ->put('/projects/'.$project->id.'/cotizaciones/'.$quotation->id, $this->payload($project, [
                'work_description' => 'Solicitud actualizada',
                'designed_solution' => '4 cámaras IP y DVR 32CH',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('quotations_tb', [
            'id' => $quotation->id,
            'work_description' => 'Solicitud actualizada',
        ]);
    }

    public function test_designed_solution_can_be_edited(): void
    {
        [$user, $project, $quotation] = $this->createStoredQuotation();

        $this->actingAs($user)
            ->put('/projects/'.$project->id.'/cotizaciones/'.$quotation->id, $this->payload($project, [
                'designed_solution' => 'Solución actualizada con DVR 16CH',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('quotations_tb', [
            'id' => $quotation->id,
            'designed_solution' => 'Solución actualizada con DVR 16CH',
        ]);
    }

    public function test_products_are_saved_with_quantities_and_prices(): void
    {
        [$user, $project] = $this->seedUserAndProject();

        $this->actingAs($user)
            ->post('/cotizaciones', $this->payload($project, [
                'lines' => [[
                    'product_name' => 'Cámara IP',
                    'quantity' => '4',
                    'brand' => 'Hikvision',
                    'serial' => 'SN-1',
                    'unit_price' => '250000',
                ]],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('quotation_lines_tb', [
            'product_name' => 'Cámara IP',
            'quantity' => '4.00',
            'brand' => 'Hikvision',
            'serial' => 'SN-1',
            'unit_price' => '250000.00',
            'line_subtotal' => '1000000.00',
        ]);
    }

    public function test_line_subtotal_is_quantity_times_unit_price(): void
    {
        [, , $quotation] = $this->createStoredQuotation([
            'lines' => [[
                'product_name' => 'Cámara IP',
                'quantity' => '4',
                'brand' => 'Hikvision',
                'serial' => 'SN-1',
                'unit_price' => '250000',
            ]],
        ]);

        $line = QuotationLine::query()->where('quotation_id', $quotation->id)->firstOrFail();
        $this->assertSame('1000000.00', (string) $line->line_subtotal);
    }

    public function test_quotation_vat_and_total_use_existing_rules(): void
    {
        [, , $quotation] = $this->createStoredQuotation([
            'lines' => [[
                'product_name' => 'Cámara IP',
                'quantity' => '4',
                'brand' => 'Hikvision',
                'serial' => 'SN-1',
                'unit_price' => '250000',
            ]],
        ]);

        $quotation->refresh();
        $this->assertSame('1000000.00', (string) $quotation->subtotal);
        $this->assertSame('160000.00', (string) $quotation->vat_amount);
        $this->assertSame('1160000.00', (string) $quotation->total);
    }

    public function test_quotation_show_displays_proposal_without_brand_or_serial_columns(): void
    {
        [$user, $project, $quotation] = $this->createStoredQuotation();

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'/cotizaciones/'.$quotation->id)
            ->assertOk()
            ->assertSee('Solicitud')
            ->assertSee('Cobertura CCTV en accesos')
            ->assertSee('Solución diseñada')
            ->assertSee('4 cámaras IP y DVR 32CH')
            ->assertSee('Propuesta económica')
            ->assertSee('Producto')
            ->assertSee('Cantidad')
            ->assertSee('Valor unitario')
            ->assertSee('Valor subtotal')
            ->assertSee('Cámara IP')
            ->assertSee('2.00')
            ->assertSee('100.00')
            ->assertSee('200.00')
            ->assertDontSee('Hikvision')
            ->assertDontSee('SN-1');
    }

    public function test_quotation_pdf_includes_solicitud_solution_and_economic_columns_only(): void
    {
        [$user, $project, $quotation] = $this->createStoredQuotation();
        $entity = app(QuotationRepositoryInterface::class)
            ->findById(QuotationId::fromInt((int) $quotation->id));

        $html = view('pdf.quotations.show', [
            'quotation' => $entity,
            'projectName' => $project->name,
            'lines' => $entity->lines(),
            'company' => app(EloquentCompanyIdentity::class)->snapshot(),
            'logoDataUri' => null,
            'signatureDataUri' => null,
            'signatoryName' => null,
            'signatoryCompany' => null,
        ])->render();

        $this->assertStringContainsString('Solicitud', $html);
        $this->assertStringContainsString('Cobertura CCTV en accesos', $html);
        $this->assertStringContainsString('Solución diseñada', $html);
        $this->assertStringContainsString('4 cámaras IP y DVR 32CH', $html);
        $this->assertStringContainsString('Producto', $html);
        $this->assertStringContainsString('Cantidad', $html);
        $this->assertStringContainsString('Valor unitario', $html);
        $this->assertStringContainsString('Valor subtotal', $html);
        $this->assertStringContainsString('Cámara IP', $html);
        $this->assertStringNotContainsString('>Marca</th>', $html);
        $this->assertStringNotContainsString('>Serie</th>', $html);
        $this->assertStringNotContainsString('Hikvision', $html);
        $this->assertStringNotContainsString('SN-1', $html);

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'/cotizaciones/'.$quotation->id.'/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_configured_logo_appears_top_right_in_quotation_pdf_without_distortion(): void
    {
        Storage::fake('public');
        [$user, $project, $quotation] = $this->createStoredQuotation();

        $this->actingAs($user)->put('/configuracion', [
            'name' => $user->name,
            'email' => $user->email,
            'vat_rate_percent' => '16',
            'company_name' => 'Management CCTV',
            'company_logo' => $this->fakePngUpload('logo.png'),
        ])->assertRedirect('/configuracion');

        $identity = app(EloquentCompanyIdentity::class);
        $entity = app(QuotationRepositoryInterface::class)
            ->findById(QuotationId::fromInt((int) $quotation->id));

        $html = view('pdf.quotations.show', [
            'quotation' => $entity,
            'projectName' => $project->name,
            'lines' => $entity->lines(),
            'company' => $identity->snapshot(),
            'logoDataUri' => $identity->logoDataUri(),
            'signatureDataUri' => null,
            'signatoryName' => null,
            'signatoryCompany' => null,
        ])->render();

        $this->assertNotNull($identity->logoDataUri());
        $this->assertStringContainsString('logo-company', $html);
        $this->assertStringContainsString('logo-cell', $html);
        $this->assertStringContainsString('text-align: right', $html);
        $this->assertStringContainsString('max-width: 160px', $html);
        $this->assertStringContainsString('max-height: 72px', $html);
        $this->assertStringContainsString('width: auto', $html);
        $this->assertStringContainsString('height: auto', $html);
        $this->assertDoesNotMatchRegularExpression('/class="logo-company"[^>]*(width|height)="/', $html);

        $pdf = $this->actingAs($user)
            ->get('/projects/'.$project->id.'/cotizaciones/'.$quotation->id.'/pdf');
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    /**
     * @return array{0: User, 1: Project}
     */
    private function seedUserAndProject(): array
    {
        $user = User::factory()->create();
        $project = Project::createRecord(['name' => 'Obra Centro']);
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        return [$user, $project];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: User, 1: Project, 2: Quotation}
     */
    private function createStoredQuotation(array $overrides = []): array
    {
        [$user, $project] = $this->seedUserAndProject();

        $this->actingAs($user)
            ->post('/cotizaciones', $this->payload($project, $overrides))
            ->assertRedirect();

        return [$user, $project, Quotation::query()->firstOrFail()];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Project $project, array $overrides = []): array
    {
        return array_merge([
            'project_id' => $project->id,
            'work_description' => 'Cobertura CCTV en accesos',
            'designed_solution' => '4 cámaras IP y DVR 32CH',
            'lines' => [[
                'product_name' => 'Cámara IP',
                'quantity' => '2',
                'brand' => 'Hikvision',
                'serial' => 'SN-1',
                'unit_price' => '100',
            ]],
        ], $overrides);
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
