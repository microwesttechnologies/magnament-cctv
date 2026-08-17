<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationPdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_pdf_download_returns_pdf_response(): void
    {
        [$user, $project, $quotation] = $this->createQuotationForPdf();

        $response = $this->actingAs($user)
            ->get('/projects/'.$project->id.'/cotizaciones/'.$quotation->id.'/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('cotizacion-'.$quotation->code.'.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_guest_is_redirected_away_from_quotation_pdf(): void
    {
        [, $project, $quotation] = $this->createQuotationForPdf();
        auth()->logout();
        $this->flushSession();

        $this->get('/projects/'.$project->id.'/cotizaciones/'.$quotation->id.'/pdf')
            ->assertRedirect('/login');
    }

    public function test_missing_quotation_pdf_returns_not_found(): void
    {
        [$user, $project] = $this->createQuotationForPdf();

        $this->actingAs($user)
            ->get('/projects/'.$project->id.'/cotizaciones/999999/pdf')
            ->assertNotFound();
    }

    public function test_quotation_pdf_from_another_project_returns_not_found(): void
    {
        [$user, , $quotation] = $this->createQuotationForPdf();
        $otherProject = Project::createRecord(['name' => 'Obra Sur']);

        $this->actingAs($user)
            ->get('/projects/'.$otherProject->id.'/cotizaciones/'.$quotation->id.'/pdf')
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Project, 2: Quotation} */
    private function createQuotationForPdf(): array
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
            ->assertRedirect();

        return [$user, $project, Quotation::query()->firstOrFail()];
    }
}
