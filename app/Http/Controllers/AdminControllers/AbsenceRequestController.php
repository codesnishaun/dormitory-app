<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbsenceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $absenceRequests = AbsenceRequest::with(['roomAssignment.user', 'roomAssignment.room', 'reviewer'])
            ->when(
                in_array($status, AbsenceRequest::STATUSES, true),
                fn ($query) => $query->where('status', $status)
            )
            ->latest()
            ->get();

        $counts = AbsenceRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.absence-requests.index', [
            'absenceRequests' => $absenceRequests,
            'statuses' => AbsenceRequest::STATUSES,
            'activeStatus' => $status,
            'summary' => [
                'total' => $counts->sum(),
                'pending' => $counts[AbsenceRequest::STATUS_PENDING] ?? 0,
                'approved' => $counts[AbsenceRequest::STATUS_APPROVED] ?? 0,
                'rejected' => $counts[AbsenceRequest::STATUS_REJECTED] ?? 0,
            ],
        ]);
    }

    public function approve(Request $request, AbsenceRequest $absenceRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->review($request, $absenceRequest, AbsenceRequest::STATUS_APPROVED, $data['admin_remarks'] ?? null);
    }

    public function reject(Request $request, AbsenceRequest $absenceRequest): RedirectResponse
    {
        $data = $request->validate([
            'admin_remarks' => ['required', 'string', 'max:1000'],
        ]);

        return $this->review($request, $absenceRequest, AbsenceRequest::STATUS_REJECTED, $data['admin_remarks']);
    }

    private function review(Request $request, AbsenceRequest $absenceRequest, string $status, ?string $adminRemarks): RedirectResponse
    {
        if (! $absenceRequest->isPending()) {
            return back()->with('error', 'This request has already been reviewed.');
        }

        $absenceRequest->update([
            'status' => $status,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_remarks' => $adminRemarks,
        ]);

        return redirect()->route('admin.absence-request.index')
            ->with('success', "Absence request {$absenceRequest->reference} was {$status}.");
    }
}