<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Dvr;
use App\Models\FloorPlan;
use App\Models\Project;
use App\Models\ProjectCamera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ProjectCameraController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateCamera($request, $project);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('camera_photos', 'public');
        }

        $project->projectCameras()->create([
            'floor_plan_id' => $validated['floor_plan_id'],
            'dvr_id' => $validated['dvr_id'],
            'channel' => $validated['channel'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'serial' => $validated['serial'] ?? null,
            'photo_path' => $photoPath,
            'pos_x' => $validated['pos_x'],
            'pos_y' => $validated['pos_y'],
        ]);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Cámara agregada al plano correctamente.')
            ->with('open_plan_viewer', true);
    }

    public function update(Request $request, Project $project, ProjectCamera $camera): RedirectResponse
    {
        abort_unless($camera->project_id === $project->id, 404);

        $validated = $this->validateCamera($request, $project, $camera);

        if ($request->hasFile('photo')) {
            $camera->deletePhoto();
            $camera->photo_path = $request->file('photo')->store('camera_photos', 'public');
        }

        $camera->fill([
            'floor_plan_id' => $validated['floor_plan_id'],
            'dvr_id' => $validated['dvr_id'],
            'channel' => $validated['channel'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'serial' => $validated['serial'] ?? null,
            'pos_x' => $validated['pos_x'],
            'pos_y' => $validated['pos_y'],
        ])->save();

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Cámara actualizada correctamente.')
            ->with('open_plan_viewer', true);
    }

    public function destroy(Project $project, ProjectCamera $camera): RedirectResponse
    {
        abort_unless($camera->project_id === $project->id, 404);

        $camera->deletePhoto();
        $camera->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Cámara eliminada correctamente.')
            ->with('open_plan_viewer', true);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCamera(Request $request, Project $project, ?ProjectCamera $existing = null): array
    {
        $validated = $request->validate([
            'floor_plan_id' => [
                'required',
                'integer',
                Rule::exists('floor_plans_tb', 'id')->where('project_id', $project->id),
            ],
            'dvr_id' => [
                'required',
                'integer',
                Rule::exists('dvrs_tb', 'id')->where('project_id', $project->id),
            ],
            'channel' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'brand' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'serial' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:5120'],
            'pos_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'pos_y' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        /** @var Dvr $dvr */
        $dvr = Dvr::query()->where('project_id', $project->id)->findOrFail($validated['dvr_id']);

        if ($validated['channel'] > $dvr->ports) {
            throw ValidationException::withMessages([
                'channel' => "El canal debe estar entre 1 y {$dvr->ports} para este DVR.",
            ]);
        }

        $channelTaken = ProjectCamera::query()
            ->where('dvr_id', $dvr->id)
            ->where('channel', $validated['channel'])
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($channelTaken) {
            throw ValidationException::withMessages([
                'channel' => 'Ese canal ya está asignado a otra cámara de este DVR.',
            ]);
        }

        /** @var FloorPlan $floorPlan */
        $floorPlan = FloorPlan::query()->where('project_id', $project->id)->findOrFail($validated['floor_plan_id']);
        unset($floorPlan);

        return $validated;
    }
}
