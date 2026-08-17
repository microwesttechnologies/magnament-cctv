<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FloorPlan;
use App\Models\Project;
use App\Models\ProjectCamera;
use App\Support\Cache\ProjectListStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class ProjectController extends Controller
{
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

    public function show(Project $project): View
    {
        $project->load([
            'dvrs' => fn ($q) => $q->withCount('cameras'),
            'floorPlans.cameras.dvr',
            'projectCameras',
            'quotations' => fn ($q) => $q
                ->select(['id', 'project_id', 'code', 'status', 'total', 'created_at'])
                ->latest()
                ->limit(10),
            'installationOrders' => fn ($q) => $q
                ->select(['id', 'project_id', 'quotation_id', 'code', 'status', 'created_at'])
                ->with('quotation:id,code')
                ->latest()
                ->limit(20),
        ]);

        $usedChannelsByDvr = $project->projectCameras
            ->groupBy('dvr_id')
            ->map(fn ($cams) => $cams->pluck('channel')->values())
            ->all();

        return view('projects.show', [
            'project' => $project,
            'totalPorts' => (int) $project->dvrs->sum('ports'),
            'totalCameras' => $project->projectCameras->count(),
            'usedChannelsByDvr' => $usedChannelsByDvr,
        ]);
    }

    public function storeFloorPlan(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'floor_plans' => ['required', 'array', 'min:1'],
            'floor_plans.*' => ['file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
            'floor_plan_names' => ['nullable', 'array'],
            'floor_plan_names.*' => ['nullable', 'string', 'max:255'],
        ]);

        $this->storeFloorPlanFiles(
            $project,
            $request->file('floor_plans', []),
            $validated['floor_plan_names'] ?? [],
            (int) $project->floorPlans()->max('sort_order') + 1,
        );

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Hoja(s) de plano agregada(s) correctamente.');
    }

    public function destroyFloorPlan(Project $project, FloorPlan $floorPlan): RedirectResponse
    {
        abort_unless($floorPlan->project_id === $project->id, 404);

        $floorPlan->deleteFile();
        $floorPlan->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Hoja de plano eliminada correctamente.');
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
     */
    private function storeFloorPlanFiles(Project $project, array $files, array $names, int $startOrder = 0): void
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
                'sort_order' => $order,
            ]);

            // Mantener compatibilidad con floor_plan_path (primera hoja del proyecto).
            if (! $project->floor_plan_path) {
                $project->update(['floor_plan_path' => $path]);
            }

            $order++;
        }
    }
}
