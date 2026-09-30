<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use Illuminate\Contracts\View\View;

class DashboardViewController extends Controller
{
    /**
     * Render the HookForge dashboard.
     */
    public function index(): View
    {
        $endpoints = Endpoint::withCount('webhookRequests')->get();

        return view('dashboard', [
            'endpoints' => $endpoints,
        ]);
    }
}
