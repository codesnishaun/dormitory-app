<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\ElectricityBill;
use App\Models\MaintenanceRequest;
use App\Models\RoomAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $request->user();
        $roomAssignment = RoomAssignment::query()
            ->with('room')
            ->where('user_id', $tenant->id)
            ->whereIn('status', ['reserved', 'occupied'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        $chargesTotal = (float) $tenant->transactions()
            ->where('type', 'charge')
            ->sum('amount');
        $paymentsTotal = (float) $tenant->transactions()
            ->where('type', 'payment')
            ->sum('amount');
        $balance = round($chargesTotal - $paymentsTotal, 2);

        $latestElectricityBill = ElectricityBill::query()
            ->whereHas('allocations', fn ($query) => $query->where('user_id', $tenant->id))
            ->with([
                'room:id,room_number',
                'allocations' => fn ($query) => $query->where('user_id', $tenant->id),
            ])
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->first();
        $recentPayments = $tenant->transactions()
            ->where('type', 'payment')
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();
        $absenceRequests = AbsenceRequest::query()
            ->whereHas('roomAssignment', fn ($query) => $query->where('user_id', $tenant->id))
            ->latest()
            ->take(3)
            ->get();
        $maintenanceRequests = MaintenanceRequest::query()
            ->where('user_id', $tenant->id)
            ->latest()
            ->take(3)
            ->get();
        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->take(3)
            ->get();

        return view('tenant.dashboard', compact(
            'roomAssignment',
            'chargesTotal',
            'paymentsTotal',
            'balance',
            'latestElectricityBill',
            'recentPayments',
            'absenceRequests',
            'maintenanceRequests',
            'announcements',
        ));
    }
}
