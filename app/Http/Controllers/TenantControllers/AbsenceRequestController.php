<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRequest;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment = $request->user()
            ->roomAssignments()
            ->whereIn('status', ['reserved', 'occupied'])
            ->latest('id')
            ->first();

        if (! $assignment) {
            return back()
                ->withInput()
                ->withErrors(['reason' => 'You need an active room assignment before you can file an absence request.']);
        }

        AbsenceRequest::create([
            'room_assignment_id' => $assignment->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'remarks' => $data['remarks'] ?? null,
            'status' => AbsenceRequest::STATUS_PENDING,
        ]);

        return redirect()->route('tenant.absence-request')
            ->with('success', 'Your absence request has been submitted.');
    }
}