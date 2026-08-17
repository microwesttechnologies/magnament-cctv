<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Dvr;
use App\Models\Project;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class DvrController extends Controller
{
    public function store(Request $request, Project $project): RedirectResponse
    {
        $validated = $this->validateDvr($request);
        $project->dvrs()->create($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'DVR agregado correctamente.');
    }

    public function update(Request $request, Project $project, Dvr $dvr): RedirectResponse
    {
        abort_unless($dvr->project_id === $project->id, 404);

        $validated = $this->validateDvr($request);
        $dvr->update($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'DVR actualizado correctamente.');
    }

    public function destroy(Project $project, Dvr $dvr): RedirectResponse
    {
        abort_unless($dvr->project_id === $project->id, 404);

        if ($dvr->cameras()->exists()) {
            return back()->withErrors([
                'dvr' => 'No se puede eliminar el DVR: tiene cámaras asignadas en el plano. Elimina o reasigna esas cámaras primero.',
            ]);
        }

        $dvr->delete();

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'DVR eliminado correctamente.');
    }

    public function show(Project $project, Dvr $dvr): View
    {
        abort_unless($dvr->project_id === $project->id, 404);

        $dvr->load(['supports.staff', 'supports.evidences']);
        $dvr->loadCount('cameras');

        $technicians = Staff::query()
            ->where('role', 'tecnico')
            ->where('status', 'activo')
            ->orderBy('name')
            ->get();

        return view('projects.dvrs.show', [
            'project' => $project,
            'dvr' => $dvr,
            'technicians' => $technicians,
        ]);
    }

    public function storeSupport(Request $request, Project $project, Dvr $dvr): RedirectResponse
    {
        abort_unless($dvr->project_id === $project->id, 404);

        $validated = $request->validate([
            'staff_id' => [
                'required',
                'integer',
                Rule::exists('staff_tb', 'id')->where('role', 'tecnico')->where('status', 'activo'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'evidences' => ['nullable', 'array'],
            'evidences.*' => ['file', 'mimes:png,jpg,jpeg,pdf', 'max:5120'],
        ]);

        $support = $dvr->supports()->create([
            'staff_id' => $validated['staff_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        foreach ($request->file('evidences', []) as $file) {
            if (! $file) {
                continue;
            }
            $path = $file->store('dvr_support_evidences', 'public');
            $support->evidences()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        return redirect()
            ->route('projects.dvrs.show', [$project, $dvr])
            ->with('status', 'Soporte registrado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDvr(Request $request): array
    {
        return $request->validate([
            'brand' => ['nullable', 'string', 'max:255'],
            'serial_model' => ['nullable', 'string', 'max:255'],
            'ports' => ['required', 'integer', 'min:1', 'max:128'],
            'disks' => ['nullable', 'integer', 'min:0'],
            'ip_address' => ['nullable', 'ip'],
            'physical_location' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
