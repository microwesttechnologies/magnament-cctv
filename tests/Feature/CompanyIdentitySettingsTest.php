<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Infrastructure\Settings\EloquentCompanyIdentity;
use App\Models\AppSetting;
use App\Models\User;
use App\Support\Cache\CacheKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyIdentitySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_replace_and_delete_company_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $this->actingAs($user)
            ->put('/configuracion', [
                'name' => $user->name,
                'email' => $user->email,
                'vat_rate_percent' => '16',
                'company_name' => 'Management CCTV',
                'company_nit' => '900123456',
                'company_phone' => '3001234567',
                'company_email' => 'contacto@managementcctv.test',
                'company_logo' => $this->fakePngUpload('logo.png'),
            ])
            ->assertRedirect('/configuracion');

        $identity = app(EloquentCompanyIdentity::class);
        $first = $identity->snapshot();
        $this->assertSame('Management CCTV', $first['name']);
        $this->assertNotNull($first['logo_path']);
        Storage::disk('public')->assertExists($first['logo_path']);
        $this->assertTrue(Cache::has(CacheKeys::SETTINGS_COMPANY));

        $this->actingAs($user)
            ->put('/configuracion', [
                'name' => $user->name,
                'email' => $user->email,
                'vat_rate_percent' => '16',
                'company_name' => 'Management CCTV',
                'company_logo' => $this->fakePngUpload('logo-2.png'),
            ])
            ->assertRedirect('/configuracion');

        $replaced = $identity->snapshot();
        $this->assertNotSame($first['logo_path'], $replaced['logo_path']);
        Storage::disk('public')->assertMissing($first['logo_path']);
        Storage::disk('public')->assertExists($replaced['logo_path']);

        $this->actingAs($user)
            ->put('/configuracion', [
                'name' => $user->name,
                'email' => $user->email,
                'vat_rate_percent' => '16',
                'company_name' => 'Management CCTV',
                'remove_company_logo' => '1',
            ])
            ->assertRedirect('/configuracion');

        $removed = $identity->snapshot();
        $this->assertNull($removed['logo_path']);
        Storage::disk('public')->assertMissing($replaced['logo_path']);
    }

    public function test_guest_cannot_change_company_logo(): void
    {
        Storage::fake('public');

        $this->put('/configuracion', [
            'name' => 'Invitado',
            'email' => 'guest@example.test',
            'vat_rate_percent' => '16',
            'company_logo' => $this->fakePngUpload('logo.png'),
        ])->assertRedirect('/login');

        $this->assertDatabaseMissing('app_settings_tb', [
            'key' => EloquentCompanyIdentity::LOGO_KEY,
        ]);
    }

    public function test_company_identity_is_cached_and_invalidated_on_update(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $identity = app(EloquentCompanyIdentity::class);
        $identity->snapshot();
        $this->assertTrue(Cache::has(CacheKeys::SETTINGS_COMPANY));

        $this->actingAs($user)->put('/configuracion', [
            'name' => $user->name,
            'email' => $user->email,
            'vat_rate_percent' => '16',
            'company_name' => 'Nueva identidad',
        ])->assertRedirect('/configuracion');

        $this->assertFalse(Cache::has(CacheKeys::SETTINGS_COMPANY));
        $this->assertSame('Nueva identidad', $identity->snapshot()['name']);
    }

    public function test_png_logo_with_spaces_in_filename_is_accepted(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $path = base_path('tests/Fixtures/company-logo-mira26.png');
        $this->assertFileExists($path);

        $this->actingAs($user)
            ->put('/configuracion', [
                'name' => $user->name,
                'email' => $user->email,
                'vat_rate_percent' => '16',
                'company_name' => 'mira26',
                'company_logo' => new UploadedFile($path, 'logo mira26.png', 'image/png', null, true),
            ])
            ->assertSessionDoesntHaveErrors('company_logo')
            ->assertRedirect('/configuracion');

        $this->assertNotNull(app(EloquentCompanyIdentity::class)->snapshot()['logo_path']);
    }

    public function test_non_image_file_is_rejected_as_company_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        AppSetting::query()->create([
            'key' => 'vat_rate_percent',
            'value' => '16.0000',
        ]);

        $this->actingAs($user)
            ->from('/configuracion')
            ->put('/configuracion', [
                'name' => $user->name,
                'email' => $user->email,
                'vat_rate_percent' => '16',
                'company_logo' => UploadedFile::fake()->createWithContent('logo.png', 'not-an-image'),
            ])
            ->assertRedirect('/configuracion')
            ->assertSessionHasErrors('company_logo');
    }

    private function fakePngUpload(string $name): UploadedFile
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        $this->assertNotFalse($png);

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
