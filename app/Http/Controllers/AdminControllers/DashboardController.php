<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\AbsenceRequest;
use App\Models\Announcement;
use App\Models\Application;
use App\Models\ElectricityBill;
use App\Models\MaintenanceRequest;
use App\Models\Room;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $roomCounts = Room::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $totalRooms = $roomCounts->sum();
        $occupiedRooms = (int) ($roomCounts['occupied'] ?? 0);
        $availableRooms = (int) ($roomCounts['available'] ?? 0);
        $reservedRooms = max($totalRooms - $occupiedRooms - $availableRooms, 0);
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $tenantFinancials = User::query()
            ->where('role', 'tenant')
            ->withSum(['transactions as charges_total' => fn ($query) => $query->where('type', 'charge')], 'amount')
            ->withSum(['transactions as payments_total' => fn ($query) => $query->where('type', 'payment')], 'amount')
            ->get(['id']);
        $tenantCount = $tenantFinancials->count();
        $outstandingBalance = $tenantFinancials->sum(fn (User $tenant): float => max(
            round((float) ($tenant->charges_total ?? 0) - (float) ($tenant->payments_total ?? 0), 2),
            0,
        ));

        $pendingApplicationCount = Application::query()
            ->where('status', 'pending')
            ->count();
        $pendingAbsenceCount = AbsenceRequest::query()
            ->where('status', AbsenceRequest::STATUS_PENDING)
            ->count();
        $pendingMaintenanceCount = MaintenanceRequest::query()
            ->where('status', 'Pending')
            ->count();

        $pendingApplications = Application::query()
            ->with('preferredRoom:id,room_number')
            ->where('status', 'pending')
            ->latest()
            ->take(3)
            ->get(['id', 'applicant_name', 'move_in_date', 'preferred_room_id', 'created_at']);
        $pendingAbsences = AbsenceRequest::query()
            ->with([
                'roomAssignment:id,user_id,room_id',
                'roomAssignment.user:id,name',
                'roomAssignment.room:id,room_number',
            ])
            ->where('status', AbsenceRequest::STATUS_PENDING)
            ->latest()
            ->take(3)
            ->get();
        $pendingMaintenance = MaintenanceRequest::query()
            ->with([
                'user:id,name',
                'roomAssignment:id,room_id',
                'roomAssignment.room:id,room_number',
            ])
            ->where('status', 'Pending')
            ->latest()
            ->take(3)
            ->get();

        $recentBills = ElectricityBill::query()
            ->with('room:id,room_number')
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->take(4)
            ->get();
        $recentTransactions = Transaction::query()
            ->with('user:id,name')
            ->latest('transaction_date')
            ->latest('id')
            ->take(5)
            ->get();
        $announcements = Announcement::published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->take(3)
            ->get();

        return view('admin.dashboard', compact(
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
            'occupancyRate',
            'tenantCount',
            'outstandingBalance',
            'pendingApplicationCount',
            'pendingAbsenceCount',
            'pendingMaintenanceCount',
            'pendingApplications',
            'pendingAbsences',
            'pendingMaintenance',
            'recentBills',
            'recentTransactions',
            'announcements',
        ));
    }
}
