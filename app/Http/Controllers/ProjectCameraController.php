<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Dvr;
use App\Models\FloorPlan;
use App\Models\Project;
use App\Models\ProjectCamera;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class ProjectCameraController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateCamera($request, $project);
        $existing = $this->findChannelCamera($project, (int) $validated['dvr_id'], (int) $validated['channel']);

        if ($existing !== null) {
            $this->assertCanPlaceOnPlan($existing, (int) $validated['floor_plan_id'], $project);
            $this->applyCameraAttributes($existing, $validated, $request, replacePhoto: $request->hasFile('photo'));
            $existing->save();

            return $this->redirectToFloorPlan($project, 'Cámara ubicada en el plano correctamente.', true);
        }

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('camera_photos', 'public')
            : null;

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

        return $this->redirectToFloorPlan($project, 'Cámara ubicada en el plano correctamente.', true);
    }

    public function update(Request $request, Project $project, ProjectCamera $camera): RedirectResponse
    {
        abort_unless($camera->project_id === $project->id, 404);

        $validated = $this->validateCamera($request, $project, $camera);
        $this->applyCameraAttributes($camera, $validated, $request, replacePhoto: $request->hasFile('photo'));
        $camera->save();

        return $this->redirectToFloorPlan($project, 'Cámara actualizada correctamente.', true);
    }

    public function updatePosition(Request $request, Project $project, ProjectCamera $camera): JsonResponse|RedirectResponse
    {
        abort_unless($camera->project_id === $project->id, 404);
        abort_unless($camera->isPlaced(), 422);

        try {
            $validated = $request->validate([
                'pos_x' => ['required', 'numeric', 'min:0', 'max:1'],
                'pos_y' => ['required', 'numeric', 'min:0', 'max:1'],
            ]);
        } catch (ValidationException $exception) {
            if ($request->expectsJson()) {
                throw $exception;
            }

            throw $exception->redirectTo($this->floorPlanUrl($project));
        }

        $camera->fill([
            'pos_x' => round((float) $validated['pos_x'], 4),
            'pos_y' => round((float) $validated['pos_y'], 4),
        ])->save();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'pos_x' => (float) $camera->pos_x,
                'pos_y' => (float) $camera->pos_y,
            ]);
        }

        return $this->redirectToFloorPlan($project, 'Posición actualizada.');
    }

    public function unplace(Project $project, ProjectCamera $camera): RedirectResponse
    {
        abort_unless($camera->project_id === $project->id, 404);

        $camera->unplace();

        return $this->redirectToFloorPlan($project, 'Ubicación quitada del plano. La cámara permanece en el proyecto.');
    }

    public function destroy(Project $project, ProjectCamera $camera): RedirectResponse
    {
        abort_unless($camera->project_id === $project->id, 404);

        $camera->deletePhoto();
        $camera->delete();

        return $this->redirectToFloorPlan($project, 'Cámara eliminada del proyecto.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCamera(Request $request, Project $project, ?ProjectCamera $existing = null): array
    {
        try {
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
                'photo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:2048'],
                'pos_x' => ['required', 'numeric', 'min:0', 'max:1'],
                'pos_y' => ['required', 'numeric', 'min:0', 'max:1'],
            ]);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($this->floorPlanUrl($project));
        }

        $validated['pos_x'] = round((float) $validated['pos_x'], 4);
        $validated['pos_y'] = round((float) $validated['pos_y'], 4);

        /** @var Dvr $dvr */
        $dvr = Dvr::query()->where('project_id', $project->id)->findOrFail($validated['dvr_id']);

        if ($validated['channel'] > $dvr->ports) {
            throw ValidationException::withMessages([
                'channel' => "El canal debe estar entre 1 y {$dvr->ports} para este DVR.",
            ])->redirectTo($this->floorPlanUrl($project));
        }

        FloorPlan::query()->where('project_id', $project->id)->findOrFail($validated['floor_plan_id']);

        if ($existing !== null) {
            $conflict = ProjectCamera::query()
                ->where('project_id', $project->id)
                ->where('dvr_id', $dvr->id)
                ->where('channel', $validated['channel'])
                ->where('id', '!=', $existing->id)
                ->first();

            if ($conflict !== null) {
                throw ValidationException::withMessages([
                    'channel' => 'Ese canal ya pertenece a otra cámara del proyecto.',
                ])->redirectTo($this->floorPlanUrl($project));
            }
        }

        return $validated;
    }

    private function findChannelCamera(Project $project, int $dvrId, int $channel): ?ProjectCamera
    {
        return ProjectCamera::query()
            ->where('project_id', $project->id)
            ->where('dvr_id', $dvrId)
            ->where('channel', $channel)
            ->first();
    }

    private function assertCanPlaceOnPlan(ProjectCamera $camera, int $floorPlanId, Project $project): void
    {
        if ((int) $camera->floor_plan_id === $floorPlanId) {
            throw ValidationException::withMessages([
                'channel' => 'Ese canal ya está ubicado en este plano.',
            ])->redirectTo($this->floorPlanUrl($project));
        }

        if ($camera->floor_plan_id !== null) {
            throw ValidationException::withMessages([
                'channel' => 'Ese canal ya está ubicado en otro plano del proyecto.',
            ])->redirectTo($this->floorPlanUrl($project));
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyCameraAttributes(ProjectCamera $camera, array $validated, Request $request, bool $replacePhoto): void
    {
        if ($replacePhoto) {
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
        ]);
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

    private function floorPlanUrl(Project $project): string
    {
        return route('projects.show', ['project' => $project, 'tab' => 'planos']);
    }
}
