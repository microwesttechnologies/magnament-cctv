<x-layout title="Configuración · CCTV Manager" active="configuracion">
    <div class="mx-auto w-full max-w-7xl">
        <x-ui.page-header
            title="Configuración"
            description="Administra tu cuenta, la identidad de la empresa y las preferencias del sistema."
        />

        @if (session('status'))
            <x-ui.alert variant="success" class="mb-6">{{ session('status') }}</x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error" class="mb-6">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </x-ui.alert>
        @endif

        <div
            class="grid w-full grid-cols-1 gap-6 lg:grid-cols-2"
            x-data="settingsIdentity({{ \Illuminate\Support\Js::from([
                'signature' => $signature,
                'storeSignatureUrl' => route('configuracion.signature.store'),
                'destroySignatureUrl' => route('configuracion.signature.destroy'),
                'csrf' => csrf_token(),
                'hasLogo' => ! empty($company['logo_path']),
            ]) }})"
        >
            <form method="POST" action="{{ route('configuracion.update') }}" enctype="multipart/form-data" class="contents">
                @csrf
                @method('PUT')

                <x-ui.card title="Datos de la cuenta" class="flex h-full min-w-0 flex-col">
                    <div class="grid gap-4">
                        <x-ui.form-field label="Nombre" for="name" required>
                            <x-ui.input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="min-h-11 w-full" />
                        </x-ui.form-field>
                        <x-ui.form-field label="Correo" for="email" required>
                            <x-ui.input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="min-h-11 w-full" />
                        </x-ui.form-field>
                        <x-ui.form-field label="Celular" for="phone" hint="Aparece en el PDF de cotizaciones como contacto del remitente.">
                            <x-ui.input id="phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="300 603 3638" class="min-h-11 w-full" />
                        </x-ui.form-field>
                    </div>

                    <div class="mt-6 border-t border-border-subtle pt-5">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Cambiar contraseña</h3>
                        <p class="mt-1 text-sm text-foreground-muted">Déjalo vacío si no quieres cambiarla.</p>

                        <div class="mt-4 grid gap-4">
                            <x-ui.form-field label="Contraseña actual" for="current_password">
                                <x-ui.input id="current_password" type="password" name="current_password" autocomplete="current-password" class="min-h-11 w-full" />
                            </x-ui.form-field>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-ui.form-field label="Nueva contraseña" for="password">
                                    <x-ui.input id="password" type="password" name="password" autocomplete="new-password" class="min-h-11 w-full" />
                                </x-ui.form-field>
                                <x-ui.form-field label="Confirmar nueva contraseña" for="password_confirmation">
                                    <x-ui.input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="min-h-11 w-full" />
                                </x-ui.form-field>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Identidad de la empresa" class="flex h-full min-w-0 flex-col">
                    <p class="text-sm text-foreground-muted">Logo y datos comerciales que aparecen en cotizaciones PDF.</p>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-ui.form-field label="Nombre comercial" for="company_name" class="sm:col-span-2">
                            <x-ui.input id="company_name" type="text" name="company_name" value="{{ old('company_name', $company['name'] ?? '') }}" placeholder="Management CCTV" class="min-h-11 w-full" />
                        </x-ui.form-field>
                        <x-ui.form-field label="NIT" for="company_nit">
                            <x-ui.input id="company_nit" type="text" name="company_nit" value="{{ old('company_nit', $company['nit'] ?? '') }}" class="min-h-11 w-full" />
                        </x-ui.form-field>
                        <x-ui.form-field label="Teléfono" for="company_phone">
                            <x-ui.input id="company_phone" type="text" name="company_phone" value="{{ old('company_phone', $company['phone'] ?? '') }}" class="min-h-11 w-full" />
                        </x-ui.form-field>
                        <x-ui.form-field label="Correo de la empresa" for="company_email" class="sm:col-span-2">
                            <x-ui.input id="company_email" type="email" name="company_email" value="{{ old('company_email', $company['email'] ?? '') }}" class="min-h-11 w-full" />
                        </x-ui.form-field>
                    </div>

                    <div class="mt-6 border-t border-border-subtle pt-5">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Logo de empresa</h3>
                        <p class="mt-1 text-xs text-foreground-muted">Aparece en la esquina superior derecha del PDF de cotizaciones.</p>

                        <div class="mt-3 rounded-lg border border-border-subtle bg-background p-4">
                            @if (! empty($company['logo_url']))
                                <img x-show="hasLogo" src="{{ $company['logo_url'] }}" alt="Logo actual" class="max-h-20 w-auto max-w-[180px] object-contain">
                            @endif
                            <p x-show="!hasLogo" x-cloak class="text-sm text-foreground-muted">No hay logo configurado.</p>
                        </div>

                        <input type="file" id="company_logo" name="company_logo" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="hidden" x-ref="logoInput" @change="logoSelected = true">
                        <input type="hidden" name="remove_company_logo" :value="removeLogo ? '1' : ''">

                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.button type="button" variant="secondary" class="min-h-11" @click="$refs.logoInput.click()">Cambiar logo</x-ui.button>
                            <x-ui.button type="button" variant="outline" class="min-h-11" x-show="hasLogo" x-cloak @click="removeLogo = true; hasLogo = false">Eliminar logo</x-ui.button>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-border-subtle pt-5">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Firma para cotizaciones</h3>
                        <p class="mt-1 text-xs text-foreground-muted">Configura la firma que aparecerá al final de las cotizaciones que envíes.</p>

                        <template x-if="signatureUrl">
                            <div class="mt-3 rounded-lg border border-border-subtle bg-background p-4">
                                <img :src="signatureUrl" alt="Firma configurada" class="max-h-24 w-auto max-w-full object-contain">
                            </div>
                        </template>

                        <template x-if="!signatureUrl">
                            <div class="mt-3 rounded-lg border border-dashed border-border bg-background p-6 text-center">
                                <p class="text-sm text-foreground-muted">No tienes una firma configurada</p>
                                <x-ui.button type="button" class="mt-4 min-h-11 w-full sm:w-auto" @click="openSignatureModal()">+ Agregar firma</x-ui.button>
                            </div>
                        </template>

                        <div class="mt-3 flex flex-wrap gap-2" x-show="signatureUrl" x-cloak>
                            <x-ui.button type="button" variant="secondary" class="min-h-11" @click="openSignatureModal()">Cambiar firma</x-ui.button>
                            <x-ui.button type="button" variant="outline" class="min-h-11" @click="deleteSignature()">Eliminar firma</x-ui.button>
                        </div>

                        <p x-show="signatureStatus" x-cloak class="mt-2 text-sm text-success" x-text="signatureStatus"></p>
                    </div>

                    <div class="mt-6 border-t border-border-subtle pt-5">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground-muted">Configuración comercial</h3>
                        <p class="mt-1 text-sm text-foreground-muted">Porcentaje vigente para nuevas cotizaciones en borrador. El valor aplicado se guarda históricamente en cada cotización.</p>
                        <x-ui.form-field label="IVA (%)" for="vat_rate_percent" class="mt-4 max-w-xs">
                            <x-ui.input id="vat_rate_percent" type="number" step="0.0001" min="0" max="100" name="vat_rate_percent" value="{{ old('vat_rate_percent', $vatRatePercent) }}" required class="min-h-11 w-full" />
                        </x-ui.form-field>
                    </div>
                </x-ui.card>

                <div class="flex justify-end lg:col-span-2">
                    <x-ui.button type="submit" class="min-h-11 w-full px-8 sm:w-auto">Guardar cambios</x-ui.button>
                </div>
            </form>

            {{-- Modal agregar firma --}}
            <div x-show="modalOpen" x-cloak class="fixed inset-0 z-[80] flex items-end justify-center p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="signature-modal-title">
                <div class="absolute inset-0 bg-primary/40" @click="closeSignatureModal()" x-show="modalOpen" x-transition.opacity></div>
                <div
                    class="relative w-full max-w-2xl rounded-xl border border-border bg-surface shadow-lg"
                    x-show="modalOpen"
                    x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
                    x-transition:enter-start="opacity-0 scale-[0.98] translate-y-1.5"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                >
                    <div class="border-b border-border-subtle px-6 py-4">
                        <h2 id="signature-modal-title" class="text-lg font-semibold text-foreground">Agregar firma</h2>
                        <p class="mt-1 text-sm text-foreground-muted">Selecciona cómo deseas crear tu firma.</p>
                    </div>

                    <div class="space-y-4 px-6 py-4" x-show="modalStep === 'choose'">
                        <button type="button" class="w-full rounded-xl border border-border bg-background p-4 text-left transition hover:border-accent hover:shadow-sm" @click="modalStep = 'upload'">
                            <p class="text-base font-semibold">Cargar imagen</p>
                            <p class="mt-1 text-sm text-foreground-muted">Usa una imagen de tu firma (PNG, JPG o WebP).</p>
                            <span class="mt-3 inline-flex min-h-11 items-center rounded-md bg-accent px-4 text-sm font-medium text-on-accent">Seleccionar imagen</span>
                        </button>
                        <button type="button" class="w-full rounded-xl border border-border bg-background p-4 text-left transition hover:border-accent hover:shadow-sm" @click="startDrawMode()">
                            <p class="text-base font-semibold">Dibujar firma</p>
                            <p class="mt-1 text-sm text-foreground-muted">Dibuja tu firma directamente en el lienzo.</p>
                            <span class="mt-3 inline-flex min-h-11 items-center rounded-md bg-accent px-4 text-sm font-medium text-on-accent">Dibujar firma</span>
                        </button>
                    </div>

                    <div class="space-y-4 px-6 py-4" x-show="modalStep === 'upload'" x-cloak>
                        <input type="file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" class="hidden" x-ref="signatureFile" @change="previewUpload($event)">
                        <div
                            class="rounded-xl border border-dashed border-border bg-background p-8 text-center"
                            @click="$refs.signatureFile.click()"
                            @dragover.prevent
                            @drop.prevent="handleDrop($event)"
                        >
                            <p class="text-sm font-medium">Arrastra tu firma aquí</p>
                            <p class="mt-1 text-xs text-foreground-muted">o haz clic para seleccionar archivo</p>
                            <img x-show="uploadPreview" :src="uploadPreview" alt="Vista previa" class="mx-auto mt-4 max-h-32 object-contain" x-cloak>
                        </div>
                        <div class="flex justify-end gap-2">
                            <x-ui.button type="button" variant="outline" class="min-h-11" @click="modalStep = 'choose'">Cancelar</x-ui.button>
                            <x-ui.button type="button" class="min-h-11" @click="saveUploadedSignature()" x-bind:disabled="!uploadPreview || saving">Guardar firma</x-ui.button>
                        </div>
                    </div>

                    <div class="space-y-4 px-6 py-4" x-show="modalStep === 'draw'" x-cloak>
                        <p class="text-sm text-foreground-muted">Dibuja tu firma aquí</p>
                        <div class="overflow-hidden rounded-xl border border-border bg-white">
                            <canvas x-ref="signatureCanvas" class="h-48 w-full touch-none" width="640" height="192"></canvas>
                        </div>
                        <div class="flex flex-wrap justify-between gap-2">
                            <x-ui.button type="button" variant="ghost" class="min-h-11" @click="clearCanvas()">Limpiar</x-ui.button>
                            <div class="flex gap-2">
                                <x-ui.button type="button" variant="outline" class="min-h-11" @click="modalStep = 'choose'">Cancelar</x-ui.button>
                                <x-ui.button type="button" class="min-h-11" @click="saveDrawnSignature()" x-bind:disabled="saving">Guardar firma</x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>
