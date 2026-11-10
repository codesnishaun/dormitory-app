<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Services\TenantRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request): View
    {
        $requests = MaintenanceRequest::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return view('tenant.maintenance', [
            'requests' => $requests,
            'categories' => MaintenanceRequest::CATEGORIES,
            'priorities' => array_reverse(MaintenanceRequest::PRIORITIES),
        ]);
    }

    public function store(Request $request, TenantRequestService $requests): RedirectResponse
    {
        $requests->createMaintenanceRequest(
            $request->user(),
            $request->only(['title', 'category', 'priority', 'description']),
        );

        return redirect()->route('tenant.maintenance')
            ->with('success', 'Your request has been submitted.');
    }
}
