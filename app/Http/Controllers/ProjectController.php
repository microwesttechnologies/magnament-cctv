<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FloorPlan;
use App\Models\Project;
use App\Models\ProjectCamera;
use App\Support\Cache\CacheInvalidator;
use App\Support\Cache\ProjectListStats;
use App\Support\FloorPlans\ProjectFloorPlanPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ProjectController extends Controller
{
    /** @var list<string> */
    public const SHOW_TABS = [
        'resumen',
        'planos',
        'info',
        'cotizaciones',
        'ordenes',
        'dvr',
        'camaras',
        'inventario',
        'trazabilidad',
    ];

    public function index(ProjectListStats $stats): View
    {
        $projects = Project::query()
            ->select(['id', 'code', 'name', 'status', 'city', 'neighborhood', 'address', 'created_at'])
            ->withCount('dvrs')
            ->withSum('dvrs', 'ports')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('projects', [
            'projects' => $projects,
            'stats' => $stats->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'floor_plans' => ['nullable', 'array'],
            'floor_plans.*' => ['file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            'floor_plan_names' => ['nullable', 'array'],
            'floor_plan_names.*' => ['nullable', 'string', 'max:255'],
            'dvrs' => ['nullable', 'array'],
            'dvrs.*.brand' => ['nullable', 'string', 'max:255'],
            'dvrs.*.serial_model' => ['nullable', 'string', 'max:255'],
            'dvrs.*.ports' => ['nullable', 'integer', 'min:1'],
            'dvrs.*.disks' => ['nullable', 'integer', 'min:0'],
            'dvrs.*.ip_address' => ['nullable', 'ip'],
            'dvrs.*.physical_location' => ['nullable', 'string', 'max:255'],
        ]);

        $status = $request->input('action') === 'draft' ? 'borrador' : 'activo';

        $project = Project::createRecord([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'address' => $validated['address'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'city' => $validated['city'] ?? null,
            'status' => $status,
        ]);

        $this->storeFloorPlanFiles(
            $project,
            $request->file('floor_plans', []),
            $validated['floor_plan_names'] ?? [],
        );

        foreach ($validated['dvrs'] ?? [] as $dvr) {
            if (($dvr['brand'] ?? '') === '' && ($dvr['serial_model'] ?? '') === '') {
                continue;
            }

            $project->dvrs()->create([
                'brand' => $dvr['brand'] ?? null,
                'serial_model' => $dvr['serial_model'] ?? null,
                'ports' => $dvr['ports'] ?? 4,
                'disks' => $dvr['disks'] ?? 1,
                'ip_address' => $dvr['ip_address'] ?? null,
                'physical_location' => $dvr['physical_location'] ?? null,
            ]);
        }

        return redirect()->route('projects')->with('status', 'Proyecto creado correctamente.');
    }

    public function show(Request $request, Project $project): View
    {
        $project->load([
            'dvrs' => fn ($q) => $q->withCount('cameras'),
            'floorPlans.cameras.dvr',
            'projectCameras.dvr',
            'projectCameras.floorPlan',
            'quotations' => fn ($q) => $q
                ->select(['id', 'project_id', 'code', 'status', 'total', 'created_at'])
                ->latest()
                ->limit(10),
            'installationOrders' => fn ($q) => $q
                ->select(['id', 'project_id', 'quotation_id', 'code', 'status', 'created_at'])
                ->with('quotation:id,code')
                ->latest()
                ->limit(20),
            'serviceOrders' => fn ($q) => $q
                ->select(['id', 'project_id', 'staff_id', 'code', 'description', 'priority', 'status', 'created_at'])
                ->with('technician:id,name')
                ->latest()
                ->limit(20),
        ]);

        $usedChannelsByDvr = $project->projectCameras
            ->groupBy('dvr_id')
            ->map(fn ($cams) => $cams->pluck('channel')->values())
            ->all();

        $planPayload = ProjectFloorPlanPayload::for($project);

        return view('projects.show', [
            'project' => $project,
            'totalPorts' => (int) $project->dvrs->sum('ports'),
            'totalCameras' => $project->projectCameras->count(),
            'usedChannelsByDvr' => $usedChannelsByDvr,
            'planSheets' => $planPayload['sheets'],
            'planDvrs' => $planPayload['dvrs'],
            'activeTab' => $this->resolveShowTab($request),
        ]);
    }

    public function storeFloorPlan(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'floor_plans' => ['required', 'array', 'min:1'],
            'floor_plans.*' => ['file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            'floor_plan_names' => ['nullable', 'array'],
            'floor_plan_names.*' => ['nullable', 'string', 'max:255'],
            'floor_plan_descriptions' => ['nullable', 'array'],
            'floor_plan_descriptions.*' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:activo,archivado'],
        ]);

        $this->storeFloorPlanFiles(
            $project,
            $request->file('floor_plans', []),
            $validated['floor_plan_names'] ?? [],
            (int) $project->floorPlans()->max('sort_order') + 1,
            $validated['floor_plan_descriptions'] ?? [],
            $validated['status'] ?? 'activo',
        );

        return $this->redirectToFloorPlan($project, 'Hoja(s) de plano agregada(s) correctamente.');
    }

    public function updateFloorPlan(Request $request, Project $project, FloorPlan $floorPlan): RedirectResponse
    {
        abort_unless($floorPlan->project_id === $project->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:activo,archivado'],
            'file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('file')) {
            $floorPlan->deleteFile();
            $floorPlan->path = $request->file('file')->store('floor_plans', 'public');
            if ($project->floor_plan_path === null) {
                $project->update(['floor_plan_path' => $floorPlan->path]);
            }
        }

        $floorPlan->fill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
        ])->save();

        return $this->redirectToFloorPlan($project, 'Plano actualizado correctamente.');
    }

    public function reorderFloorPlans(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer'],
        ]);

        $ids = array_map('intval', $validated['order']);
        $owned = $project->floorPlans()->whereIn('id', $ids)->pluck('id')->all();

        abort_unless(count($owned) === count($ids), 404);

        foreach ($ids as $index => $id) {
            FloorPlan::query()->where('id', $id)->where('project_id', $project->id)->update(['sort_order' => $index]);
        }

        CacheInvalidator::projectPlans((int) $project->id);

        return $this->redirectToFloorPlan($project, 'Orden de planos actualizado.');
    }

    public function destroyFloorPlan(Project $project, FloorPlan $floorPlan): RedirectResponse
    {
        abort_unless($floorPlan->project_id === $project->id, 404);

        $floorPlan->deleteFile();
        $floorPlan->delete();

        return $this->redirectToFloorPlan($project, 'Hoja de plano eliminada correctamente.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['required', 'string'],
        ]);

        if ($request->input('confirmation') !== $project->name) {
            return back()->withErrors([
                'confirmation' => 'La clave de confirmación no coincide con el nombre del proyecto.',
            ]);
        }

        $project->load(['floorPlans', 'projectCameras']);

        foreach ($project->projectCameras as $camera) {
            $camera->deletePhoto();
        }

        foreach ($project->floorPlans as $floorPlan) {
            $floorPlan->deleteFile();
        }

        if ($project->floor_plan_path && Storage::disk('public')->exists($project->floor_plan_path)) {
            Storage::disk('public')->delete($project->floor_plan_path);
        }

        // Los DVRs y floor plans se eliminan en cascada por la llave foránea.
        $project->delete();

        return redirect()->route('projects')->with('status', 'Proyecto eliminado correctamente.');
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     * @param  array<int, string|null>  $names
     * @param  array<int, string|null>  $descriptions
     */
    private function storeFloorPlanFiles(
        Project $project,
        array $files,
        array $names,
        int $startOrder = 0,
        array $descriptions = [],
        string $status = 'activo',
    ): void
    {
        $order = $startOrder;

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('floor_plans', 'public');
            $name = trim((string) ($names[$index] ?? ''));

            if ($name === '') {
                $name = 'Hoja '.($order + 1);
            }

            $project->floorPlans()->create([
                'path' => $path,
                'name' => $name,
                'description' => trim((string) ($descriptions[$index] ?? '')) ?: null,
                'sort_order' => $order,
                'status' => $status,
            ]);

            // Mantener compatibilidad con floor_plan_path (primera hoja del proyecto).
            if (! $project->floor_plan_path) {
                $project->update(['floor_plan_path' => $path]);
            }

            $order++;
        }
    }

    private function resolveShowTab(Request $request): string
    {
        $tab = $request->query('tab');
        if ($tab === 'cctv' || $tab === 'plano') {
            $tab = 'planos';
        }

        if (is_string($tab) && in_array($tab, self::SHOW_TABS, true)) {
            return $tab;
        }

        if ($request->session()->has('open_plan_viewer') || $request->session()->has('errors')) {
            return 'planos';
        }

        return 'resumen';
    }

    private function redirectToFloorPlan(Project $project, string $status, bool $openViewer = false): RedirectResponse
    {
        $redirect = redirect()
            ->route('projects.show', ['project' => $project, 'tab' => 'planos'])
            ->with('status', $status);

        if ($openViewer) {
            return $redirect->with('open_plan_viewer', true);
        }

        return $redirect;
    }
}
