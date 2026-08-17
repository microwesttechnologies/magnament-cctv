<?php

declare(strict_types=1);

namespace App\Http\Controllers\Technician;

use App\Application\ServiceOrder\ServiceOrderWorkflow;
use App\Domain\ServiceOrder\Exceptions\InvalidServiceOrderTransition;
use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderEvidence;
use App\Models\TechnicianNotification;
use App\Rules\ValidRasterImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
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

    public function evidence(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): JsonResponse|RedirectResponse
    {
        $this->authorize('addEvidence', $order);

        if (! $order->canAddPhotoEvidence()) {
            $message = 'Máximo 3 evidencias por orden.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'errors' => ['evidence' => [$message]]], 422);
            }

            return back()->withErrors(['evidence' => $message]);
        }

        $request->validate([
            'evidence' => ['required', 'file', new ValidRasterImage(['image/jpeg', 'image/png', 'image/webp'], 'La evidencia')],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $evidence = $workflow->addEvidence(
            $order,
            $request->file('evidence'),
            Auth::id(),
            $request->user()->staff?->id,
            $request->string('description')->toString() ?: null,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Evidencia agregada.',
                'evidence' => [
                    'id' => $evidence->id,
                    'url' => $evidence->url(),
                ],
            ]);
        }

        return back()->with('status', 'Evidencia agregada.');
    }

    public function destroyEvidence(Request $request, ServiceOrder $order, ServiceOrderEvidence $evidence): JsonResponse|RedirectResponse
    {
        $this->authorize('addEvidence', $order);

        if ((int) $evidence->service_order_id !== (int) $order->id) {
            abort(404);
        }

        if ($order->statusEnum()->isTerminal()) {
            abort(403);
        }

        $evidence->deleteFile();
        $evidence->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Evidencia eliminada.');
    }

    public function finalize(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $this->authorize('finalize', $order);
        $validated = $request->validate([
            'result' => ['required', Rule::in(['resuelta', 'no_resuelta', 'cancelada'])],
            'observation' => ['required', 'string', 'max:5000'],
        ]);

        $order->refresh();

        $evidenceCount = $order->photoEvidenceCount();
        if ($evidenceCount < 1) {
            return back()->withErrors(['observation' => 'Debes agregar al menos 1 evidencia fotográfica.']);
        }
        if ($evidenceCount > 3) {
            return back()->withErrors(['observation' => 'Máximo 3 evidencias por orden.']);
        }

        try {
            match ($validated['result']) {
                'resuelta' => $workflow->resolve($order, $validated['observation'], Auth::id()),
                'no_resuelta' => $workflow->markUnresolved($order, $validated['observation'], Auth::id()),
                'cancelada' => $workflow->cancel($order, $validated['observation'], Auth::id()),
            };
        } catch (InvalidServiceOrderTransition $e) {
            return back()->withErrors(['observation' => $e->getMessage()]);
        }

        $message = match ($validated['result']) {
            'resuelta' => 'Orden finalizada correctamente.',
            'no_resuelta' => 'Orden marcada como no resuelta.',
            'cancelada' => 'Orden cancelada correctamente.',
        };

        return redirect()->route('technician.orders.show', $order)->with('status', $message);
    }

    public function resolve(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $request->merge([
            'result' => 'resuelta',
            'observation' => $request->input('resolution_notes'),
        ]);

        return $this->finalize($request, $order, $workflow);
    }

    public function cancel(Request $request, ServiceOrder $order, ServiceOrderWorkflow $workflow): RedirectResponse
    {
        $request->merge([
            'result' => 'cancelada',
            'observation' => $request->input('cancellation_reason'),
        ]);

        return $this->finalize($request, $order, $workflow);
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
