<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InstallationOrder;
use App\Models\Project;
use App\Models\TraceabilityEvent;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TraceabilityController extends Controller
{
    public function index(Request $request): View
    {
        $projectId = $request->integer('project_id') ?: null;

        $events = TraceabilityEvent::query()
            ->with(['project', 'quotation', 'order', 'user'])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->limit(200)
            ->get();

        return view('traceability.index', [
            'events' => $events,
            'projects' => Project::query()->orderBy('name')->get(['id', 'name', 'code']),
            'selectedProjectId' => $projectId,
        ]);
    }
}
