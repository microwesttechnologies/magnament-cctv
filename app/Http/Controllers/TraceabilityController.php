<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TraceabilityEvent;
use App\Support\Cache\CatalogCache;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TraceabilityController extends Controller
{
    public function index(Request $request, CatalogCache $catalog): View
    {
        $projectId = $request->integer('project_id') ?: null;

        $events = TraceabilityEvent::query()
            ->select(['id', 'project_id', 'quotation_id', 'event_type', 'title', 'created_at'])
            ->with('project:id,name,code')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('traceability.index', [
            'events' => $events,
            'projects' => $catalog->projectsPicker(),
            'selectedProjectId' => $projectId,
        ]);
    }
}
