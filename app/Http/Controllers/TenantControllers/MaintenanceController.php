<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(MaintenanceRequest::CATEGORIES)],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $assignment = $request->user()
            ->roomAssignments()
            ->whereIn('status', ['reserved', 'occupied'])
            ->latest('id')
            ->first();

        if (! $assignment) {
            return back()
                ->withInput()
                ->withErrors(['title' => 'You need an active room assignment before you can report an issue.']);
        }
        

        MaintenanceRequest::create([
            'user_id' => $request->user()->id,
            'room_assignment_id' => $assignment->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'description' => $data['description'],
            'status' => 'Pending',
        ]);

        return redirect()->route('tenant.maintenance')
            ->with('success', 'Your request has been submitted.');
    }
}