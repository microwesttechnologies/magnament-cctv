<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Application\ServiceOrder\ServiceOrderWorkflow;
use App\Domain\ServiceOrder\Exceptions\InvalidServiceOrderTransition;
use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\ServiceOrder;
use App\Models\TechnicianNotification;
use App\Rules\ValidRasterImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class TechnicianOrderController extends Controller
{
    public function home(Request $request): View
    {
        $staffId = (int) $request->user()->staff?->id;
        $orders = ServiceOrder::query()
            ->select(['id', 'code', 'project_id', 'description', 'priority', 'status', 'scheduled_at', 'created_at'])
            ->with('project:id,name,address,city')
            ->where('staff_id', $staffId)
            ->whereIn('status', ['asignada', 'en_proceso'])
            ->latest()
            ->limit(20)
            ->get();

        return view('technician.home', [
            'orders' => $orders,
            'user' => $request->user(),
        ]);
    }

    public function index(Request $request): View
    {
        $staffId = (int) $request->user()->staff?->id;
        $status = $request->string('status')->toString();
        $query = ServiceOrder::query()
            ->select(['id', 'code', 'project_id', 'description', 'priority', 'status', 'scheduled_at', 'created_at'])
            ->with('project:id,name,address,city')
            ->where('staff_id', $staffId)
            ->latest();

        if ($status !== '' && $status !== 'todas') {
            $query->where('status', $status);
        } else {
            $query->orderByRaw("CASE status WHEN 'en_proceso' THEN 0 WHEN 'asignada' THEN 1 WHEN 'pendiente' THEN 2 ELSE 3 END");
        }

        return view('technician.orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'status' => $status !== '' ? $status : 'todas',
            'user' => $request->user(),
        ]);
    }

    public function show(Request $request, ServiceOrder $order): View
    {
        $this->authorize('view', $order);
        $order->load(['project', 'evidences', 'technician']);

        return view('technician.orders.show', [
            'order' => $order,
            'user' => $request->user(),
        ]);
    }

    public function start(ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $this->authorize('start', $order);
        try {
            $workflow->start($order, Auth::id());
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'Orden iniciada.');
    }

    public function evidence(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $this->authorize('addEvidence', $order);
        $request->validate([
            'evidence' => ['required', 'file', 'max:5120', new ValidRasterImage(['image/png'], 'La evidencia')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $workflow->addEvidence(
            $order,
            $request->file('evidence'),
            Auth::id(),
            $request->user()->staff?->id,
            $request->string('description')->toString() ?: null,
        );

        return back()->with('status', 'Evidencia PNG cargada.');
    }

    public function resolve(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $this->authorize('resolve', $order);
        $validated = $request->validate([
            'resolution_notes' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $workflow->resolve($order, $validated['resolution_notes'], Auth::id());
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['resolution_notes' => $e->getMessage()]);
        }

        return redirect()->route('technician.orders.show', $order)->with('status', 'Orden resuelta.');
    }

    public function cancel(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $workflow->cancel($order, $validated['cancellation_reason'], Auth::id());
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['cancellation_reason' => $e->getMessage()]);
        }

        return redirect()->route('technician.orders.show', $order)->with('status', 'Orden cancelada.');
    }

    public function profile(Request $request): View
    {
        return view('technician.profile', [
            'user' => $request->user()->load('staff'),
        ]);
    }

    public function notifications(Request $request): View
    {
        $userId = (int) $request->user()->id;
        TechnicianNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $items = TechnicianNotification::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate(20);

        return view('technician.notifications', [
            'notifications' => $items,
            'user' => $request->user(),
        ]);
    }

    public function subscribePush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:255'],
        ]);

        PushSubscription::query()->updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'key' => config('webpush.vapid_public'),
        ]);
    }
}
