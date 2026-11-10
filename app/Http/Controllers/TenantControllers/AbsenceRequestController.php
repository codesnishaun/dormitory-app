<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRequest;
use App\Services\TenantRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsenceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = AbsenceRequest::whereHas(
            'roomAssignment',
            fn ($query) => $query->where('user_id', $request->user()->id)
        )
            ->latest()
            ->get();

        return view('tenant.absence-requests', [
            'requests' => $requests,
        ]);
    }

    public function store(Request $request, TenantRequestService $requests): RedirectResponse
    {
        $requests->createAbsenceRequest(
            $request->user(),
            $request->only(['start_date', 'end_date', 'reason', 'remarks']),
        );

        return redirect()->route('tenant.absence-request')
            ->with('success', 'Your absence request has been submitted.');
    }
}
