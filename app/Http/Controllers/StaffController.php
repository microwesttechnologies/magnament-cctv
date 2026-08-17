<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $query = Staff::query()->withCount('tools')->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', $q)
                    ->orWhere('document_number', 'like', $q)
                    ->orWhere('email', 'like', $q);
            });
        }

        return view('staff.index', [
            'staff' => $query->get(),
            'filters' => $request->only(['role', 'status', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('staff.form', [
            'staff' => new Staff([
                'document_type' => 'CC',
                'role' => 'tecnico',
                'status' => 'activo',
            ]),
            'tools' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStaff($request);
        $tools = $validated['tools'] ?? [];
        unset($validated['tools'], $validated['photo']);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('staff_photos', 'public')
            : null;

        $staff = Staff::create([
            ...$validated,
            'photo_path' => $photoPath,
        ]);

        $this->syncTools($staff, $tools);

        return redirect()->route('staff.index')->with('status', 'Personal creado correctamente.');
    }

    public function edit(Staff $staff): View
    {
        $staff->load('tools');

        return view('staff.form', [
            'staff' => $staff,
            'tools' => $staff->tools->map(fn ($t) => [
                'name' => $t->name,
                'brand' => $t->brand,
                'reference' => $t->reference,
                'serial' => $t->serial,
            ])->values()->all(),
        ]);
    }

    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $validated = $this->validateStaff($request, $staff);
        $tools = $validated['tools'] ?? [];
        unset($validated['tools'], $validated['photo']);

        if ($request->hasFile('photo')) {
            $staff->deletePhoto();
            $staff->photo_path = $request->file('photo')->store('staff_photos', 'public');
        }

        $staff->fill($validated)->save();
        $this->syncTools($staff, $tools);

        return redirect()->route('staff.index')->with('status', 'Personal actualizado correctamente.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        if ($staff->supports()->exists()) {
            return back()->withErrors([
                'staff' => 'No se puede eliminar: tiene soportes registrados en DVRs. Márcalo como inactivo.',
            ]);
        }

        $staff->deletePhoto();
        $staff->delete();

        return redirect()->route('staff.index')->with('status', 'Personal eliminado correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStaff(Request $request, ?Staff $existing = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:CC,CE,Pasaporte,PPT'],
            'document_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('staff_tb', 'document_number')->ignore($existing?->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:5120'],
            'role' => ['required', 'in:supervisor,tecnico'],
            'status' => ['required', 'in:activo,inactivo'],
            'tools' => ['nullable', 'array'],
            'tools.*.name' => ['nullable', 'string', 'max:255'],
            'tools.*.brand' => ['nullable', 'string', 'max:255'],
            'tools.*.reference' => ['nullable', 'string', 'max:255'],
            'tools.*.serial' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tools
     */
    private function syncTools(Staff $staff, array $tools): void
    {
        $staff->tools()->delete();

        foreach ($tools as $tool) {
            $name = trim((string) ($tool['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $staff->tools()->create([
                'name' => $name,
                'brand' => $tool['brand'] ?? null,
                'reference' => $tool['reference'] ?? null,
                'serial' => $tool['serial'] ?? null,
            ]);
        }
    }
}
