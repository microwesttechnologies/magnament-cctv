<?php

namespace App\Http\Controllers;

use App\Support\Cache\DashboardSnapshot;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardSnapshot $snapshot): View
    {
        $data = $snapshot->get();

        return view('dashboard', [
            'stats' => $data['stats'],
            'attention' => $data['attention'],
            'recentActivity' => $data['recentActivity'],
        ]);
    }
}
