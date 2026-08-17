<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InstallationOrder;
use App\Models\Project;
use Illuminate\View\View;

final class InstallationOrderController extends Controller
{
    public function show(Project $project, InstallationOrder $order): View
    {
        abort_unless((int) $order->project_id === (int) $project->id, 404);
        $order->load(['quotation:id,code,total,vat_rate_percent']);

        return view('orders.show', [
            'project' => $project,
            'order' => $order,
        ]);
    }
}
