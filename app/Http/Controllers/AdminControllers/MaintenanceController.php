<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    private const PRIORITY_CLASSES = [
        'High' => 'bg-danger/10 text-danger',
        'Medium' => 'bg-copper/15 text-copperDeep',
        'Low' => 'bg-paper2 text-inkSoft',
    ];

    public function index(): View
    {
        $requests = MaintenanceRequest::with(['user', 'roomAssignment.room'])
            ->latest()
            ->get();

        $maintenanceRequests = $requests->map(fn (MaintenanceRequest $item): array => [
            'db_id' => $item->id,
            'id' => $item->reference,
            'title' => $item->title,
            'category' => $item->category,
            'priority' => $item->priority,
            'priority_class' => self::PRIORITY_CLASSES[$item->priority] ?? 'bg-paper2 text-inkSoft',
            'room' => $item->roomAssignment?->room?->room_number ?? 'N/A',
            'tenant' => $item->user?->name ?? 'Unknown',
            'reported_at' => $item->created_at->toDateString(),
            'reported_at_label' => $item->created_at->format('M j, Y'),
            'description' => $item->description,
            'status' => $item->status,
            'admin_remarks' => $item->admin_remarks,
        ])->all();

        $statuses = MaintenanceRequest::STATUSES;
        $priorities = MaintenanceRequest::PRIORITIES;
        $summary = [
            'total' => $requests->count(),
            'pending' => $requests->where('status', 'Pending')->count(),
            'in_progress' => $requests->where('status', 'In Progress')->count(),
            'resolved' => $requests->where('status', 'Resolved')->count(),
        ];

        return view('admin.maintenance.index', compact('maintenanceRequests', 'priorities', 'statuses', 'summary'));
    }

    public function update(Request $request, MaintenanceRequest $maintenanceRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(MaintenanceRequest::STATUSES)],
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $maintenanceRequest->update([
            'status' => $data['status'],
            'admin_remarks' => $data['admin_remarks'] ?? null,
            'resolved_at' => $data['status'] === 'Resolved'
                ? ($maintenanceRequest->resolved_at ?? now())
                : null,
        ]);

        return redirect()->route('admin.maintenance.index')
            ->with('success', $maintenanceRequest->reference . ' updated.');
    }
}