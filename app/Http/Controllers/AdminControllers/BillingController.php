<?php

namespace App\Http\Controllers\AdminControllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function index()
    {
        $tenants = User::query()
            ->where('role', 'tenant')
            ->with([
                'roomAssignments' => fn ($q) => $q->whereIn('status', ['reserved', 'occupied'])
                    ->with('room')
                    ->orderByDesc('start_date'),
                'transactions' => fn ($q) => $q->latest('transaction_date')->latest('id'),
            ])
            ->withSum(['transactions as charges_total' => fn ($q) => $q->where('type', 'charge')], 'amount')
            ->withSum(['transactions as payments_total' => fn ($q) => $q->where('type', 'payment')], 'amount')
            ->orderBy('name')
            ->get();

        $tenantBalances = $tenants->map(function (User $tenant) {
            $room = $tenant->roomAssignments->first()?->room;
            $last = $tenant->transactions->first();

            return [
                'id'       => $tenant->id,
                'name'     => $tenant->name,
                'room'     => $room?->room_number ?? '—',
                // positive = tenant still owes money
                'balance'  => round((float) $tenant->charges_total - (float) $tenant->payments_total, 2),
                'date'     => $last?->transaction_date?->toDateString(),
                'activity' => $last ? ucfirst($last->type) : null,
                'amount'   => $last ? (float) $last->amount : null,
                'history'  => $tenant->transactions->map(fn (Transaction $t) => [
                    'date'   => $t->transaction_date->toDateString(),
                    'type'   => ucfirst($t->type),
                    'title'  => $t->description ?: ucfirst($t->type),
                    'meta'   => $t->type === 'payment'
                        ? str_replace('_', ' ', ucfirst((string) $t->payment_method))
                        : ($t->due_date ? 'Due ' . $t->due_date->toDateString() : ''),
                    'amount' => (float) $t->amount,
                ])->values()->all(),
            ];
        })->values()->all();

        $roomByUser = collect($tenantBalances)->pluck('room', 'id');

        $recentTransactions = Transaction::with('user')
            ->latest('transaction_date')
            ->latest('id')
            ->take(8)
            ->get()
            ->map(fn (Transaction $t) => [
                'date'   => $t->transaction_date->toDateString(),
                'tenant' => $t->user?->name ?? 'Unknown',
                'room'   => $roomByUser[$t->user_id] ?? '—',
                'type'   => ucfirst($t->type),
                'amount' => (float) $t->amount,
                'note'   => $t->description ?: ($t->notes ?: '—'),
            ])
            ->all();

        $outstandingBalance = collect($tenantBalances)->sum(fn ($t) => max($t['balance'], 0));
        $tenantsInGoodStanding = collect($tenantBalances)->filter(fn ($t) => $t['balance'] <= 0)->count();

        $collectedThisMonth = (float) Transaction::where('type', 'payment')
            ->whereYear('transaction_date', now()->year)
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        $totalTransactions = Transaction::count();
        $billingPeriod = now()->format('F Y');

        return view('admin.billing.index', compact(
            'tenantBalances',
            'recentTransactions',
            'outstandingBalance',
            'tenantsInGoodStanding',
            'collectedThisMonth',
            'totalTransactions',
            'billingPeriod',
        ));
    }

    public function storePayment(Request $request, User $tenant): RedirectResponse
    {
        abort_unless($tenant->role === 'tenant', 404);

        $data = $request->validateWithBag('payment', [
            'amount'           => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method'   => ['required', Rule::in(['cash', 'gcash', 'bank_transfer', 'other'])],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        Transaction::create($data + [
            'user_id'     => $tenant->id,
            'type'        => 'payment',
            'description' => 'Payment',
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.billing.index')
            ->with('success', 'Payment recorded for ' . $tenant->name . '.');
    }

    public function storeCharge(Request $request, User $tenant): RedirectResponse
    {
        abort_unless($tenant->role === 'tenant', 404);

        $data = $request->validateWithBag('charge', [
            'description'      => ['required', 'string', 'max:255'],
            'amount'           => ['required', 'numeric', 'gt:0', 'max:9999999.99'],
            'transaction_date' => ['required', 'date'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:transaction_date'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        Transaction::create($data + [
            'user_id'     => $tenant->id,
            'type'        => 'charge',
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.billing.index')
            ->with('success', 'Charge added for ' . $tenant->name . '.');
    }
}