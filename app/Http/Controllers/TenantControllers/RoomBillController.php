<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\ElectricityBillAllocation;
use App\Models\RoomAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RoomBillController extends Controller
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

        $allocations = ElectricityBillAllocation::query()
            ->with([
                'electricityBill.room',
                'electricityBill.segments.meterAssignment.submeter',
                'transaction',
            ])
            ->where('user_id', $tenant->id)
            ->latest('id')
            ->get();

        $transactions = $tenant->transactions()
            ->latest('transaction_date')
            ->latest('id')
            ->get();
        $chargesTotal = (float) $transactions->where('type', 'charge')->sum('amount');
        $paymentsTotal = (float) $transactions->where('type', 'payment')->sum('amount');
        $balance = round($chargesTotal - $paymentsTotal, 2);

        return view('tenant.room-bill', compact(
            'roomAssignment',
            'allocations',
            'transactions',
            'chargesTotal',
            'paymentsTotal',
            'balance',
        ));
    }
}
