<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\ServiceOrder\ServiceOrderWorkflow;
use App\Domain\ServiceOrder\Exceptions\InvalidServiceOrderTransition;
use App\Models\ServiceOrder;
use App\Support\Cache\CatalogCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ServiceOrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = ServiceOrder::query()
            ->select([
                'id', 'code', 'project_id', 'staff_id', 'description', 'priority', 'status', 'scheduled_at', 'created_at',
            ])
            ->with(['project:id,name,code', 'technician:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }
        if ($request->filled('q')) {
            $term = str_replace(['%', '_'], '', $request->string('q')->toString());
            if ($term !== '') {
                $query->where(function ($builder) use ($term) {
                    $builder->where('code', 'like', $term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');
                });
            }
        }

        $counts = ServiceOrder::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('service-orders.index', [
            'orders' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only(['status', 'priority', 'q']),
            'counts' => [
                'pendiente' => (int) ($counts['pendiente'] ?? 0),
                'asignada' => (int) ($counts['asignada'] ?? 0),
                'en_proceso' => (int) ($counts['en_proceso'] ?? 0),
                'resuelta' => (int) ($counts['resuelta'] ?? 0),
                'cancelada' => (int) ($counts['cancelada'] ?? 0),
            ],
        ]);
    }

    public function create(CatalogCache $catalog, Request $request): View
    {
        return view('service-orders.form', [
            'order' => null,
            'projects' => $catalog->projectsPicker(),
            'technicians' => $catalog->activeTechnicians(),
            'selectedProjectId' => $request->integer('project_id') ?: null,
        ]);
    }

    public function store(Request $request, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $this->authorize('create', ServiceOrder::class);
        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects_tb,id'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['baja', 'media', 'alta'])],
            'staff_id' => ['nullable', 'integer', Rule::exists('staff_tb', 'id')->where('role', 'tecnico')->where('status', 'activo')],
            'scheduled_at' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:5000'],
        ]);

        $order = $workflow->create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('service-orders.show', $order)
            ->with('status', 'Orden creada: '.$order->code);
    }

    public function show(ServiceOrder $order): View
    {
        $this->authorize('view', $order);
        $order->load(['project', 'technician', 'evidences', 'creator', 'dvr']);

        return view('service-orders.show', [
            'order' => $order,
            'technicians' => app(CatalogCache::class)->activeTechnicians(),
        ]);
    }

    public function assign(
        Request $request,
        ServiceOrder $order,
        ServiceOrderWorkflow $workflow,
    ): RedirectResponse {
        $this->authorize('assign', $order);
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', Rule::exists('staff_tb', 'id')->where('role', 'tecnico')->where('status', 'activo')],
        ]);

        try {
            $workflow->assign($order, (int) $validated['staff_id'], Auth::id());
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['staff_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Orden asignada.');
    }

    public function reassign(
        Request $request,
        ServiceOrder $order,
        ServiceOrderWorkflow $workflow,
    ): RedirectResponse {
        $this->authorize('reassign', $order);
        $validated = $request->validate([
            'staff_id' => ['required', 'integer', Rule::exists('staff_tb', 'id')->where('role', 'tecnico')->where('status', 'activo')],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $workflow->reassign($order, (int) $validated['staff_id'], Auth::id(), $validated['reason'] ?? null);
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['staff_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Orden reasignada.');
    }

    public function updatePriority(
        Request $request,
        ServiceOrder $order,
        ServiceOrderWorkflow $workflow,
    ): RedirectResponse {
        $this->authorize('updatePriority', $order);
        $validated = $request->validate([
            'priority' => ['required', Rule::in(['baja', 'media', 'alta'])],
        ]);

        $workflow->updatePriority($order, $validated['priority'], Auth::id());

        return back()->with('status', 'Prioridad actualizada.');
    }

    public function cancel(
        Request $request,
        ServiceOrder $order,
        ServiceOrderWorkflow $workflow,
    ): RedirectResponse {
        $this->authorize('cancel', $order);
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $workflow->cancel($order, $validated['cancellation_reason'], Auth::id());
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['cancellation_reason' => $e->getMessage()]);
        }

        return back()->with('status', 'Orden cancelada.');
    }
}
