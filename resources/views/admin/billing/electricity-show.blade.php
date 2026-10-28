<x-admin-layout>
    <x-slot:title>Electricity bill {{ $bill->id }}</x-slot:title>

    <div class="mx-auto w-full max-w-5xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal">Finalized electricity bill</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-ink">{{ $bill->room->room_number }} · Bill #{{ $bill->id }}</h1>
                <p class="mt-2 text-sm text-inkSoft">{{ $bill->period_start->format('M j, Y') }} through {{ $bill->period_end->format('M j, Y') }} · Finalized {{ $bill->finalized_at->format('M j, Y g:i A') }}</p>
            </div>
            <a href="{{ route('admin.electricity-bills.index') }}" class="text-sm font-semibold text-teal hover:text-tealDeep">Back to electricity bills</a>
        </header>

        @if (session('success'))
            <div class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4"><p class="text-xs text-inkSoft">Total usage</p><p class="mt-1 font-mono text-xl font-bold">{{ number_format((float) $bill->total_kwh, 3) }} kWh</p></div>
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4">
                <p class="text-xs text-inkSoft">Cost source</p>
                <p class="mt-1 text-sm font-bold">{{ $bill->cost_source === 'provider_bill' ? 'Actual room utility-bill amount' : 'Explicit tariff estimate' }}</p>
                @if ($bill->rate_per_kwh !== null)
                    <p class="mt-1 font-mono text-xs text-inkSoft">₱{{ number_format((float) $bill->rate_per_kwh, 4) }}/kWh</p>
                @endif
            </div>
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4"><p class="text-xs text-inkSoft">Total amount</p><p class="mt-1 font-mono text-xl font-bold text-teal">₱{{ number_format((float) $bill->total_amount, 2) }}</p></div>
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4"><p class="text-xs text-inkSoft">Finalized by</p><p class="mt-1 text-sm font-semibold">{{ $bill->finalizedBy?->name ?? 'Admin account removed' }}</p></div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3" aria-labelledby="bill-segments-heading">
            <div class="border-b border-ink/8 px-5 py-4"><h2 id="bill-segments-heading" class="font-display text-lg font-bold">Meter reading snapshots</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-left text-sm">
                    <thead class="bg-paper/60 text-[10px] uppercase tracking-wider text-inkSoft">
                        <tr><th class="px-5 py-3">Meter</th><th class="px-5 py-3">Dates</th><th class="px-5 py-3">Opening</th><th class="px-5 py-3">Closing</th><th class="px-5 py-3 text-right">Usage</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink/8">
                        @foreach ($bill->segments as $segment)
                            <tr>
                                <td class="px-5 py-3 font-semibold">{{ $segment->meterAssignment->submeter->meter_number }}</td>
                                <td class="px-5 py-3 text-xs text-inkSoft">{{ $segment->segment_start->format('Y-m-d') }} – {{ $segment->segment_end->format('Y-m-d') }}</td>
                                <td class="px-5 py-3 font-mono">{{ number_format((float) $segment->opening_reading, 3) }}</td>
                                <td class="px-5 py-3 font-mono">{{ number_format((float) $segment->closing_reading, 3) }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format((float) $segment->consumption_kwh, 3) }} kWh</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3" aria-labelledby="bill-allocations-heading">
            <div class="border-b border-ink/8 px-5 py-4">
                <h2 id="bill-allocations-heading" class="font-display text-lg font-bold">Tenant charges</h2>
                <p class="mt-1 text-xs text-inkSoft">Room cost × tenant chargeable days ÷ {{ number_format($bill->allocations->sum('chargeable_days')) }} total person-days.</p>
                <p class="mt-1 text-xs text-inkSoft">Allocation method: {{ $bill->allocation_method }}.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-paper/60 text-[10px] uppercase tracking-wider text-inkSoft">
                        <tr><th class="px-5 py-3">Tenant / assignment</th><th class="px-5 py-3 text-right">Eligible days</th><th class="px-5 py-3 text-right">Approved absence days</th><th class="px-5 py-3 text-right">Chargeable days</th><th class="px-5 py-3 text-right">Final charge</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink/8">
                        @foreach ($bill->allocations as $allocation)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-semibold">{{ $allocation->tenant_name }}</p>
                                    @if ($allocation->assignment_start_date)
                                        <p class="text-xs text-inkSoft">{{ $allocation->assignment_start_date->toDateString() }} – {{ $allocation->assignment_end_date?->toDateString() ?? 'Ongoing' }}</p>
                                    @endif
                                    @foreach ($allocation->absence_snapshots ?? [] as $absence)
                                        <p class="mt-1 text-xs text-inkSoft">Approved absence #{{ $absence['absence_request_id'] }}: {{ $absence['requested_start_date'] }}–{{ $absence['requested_end_date'] }} (eligible overlap {{ $absence['eligible_start_date'] }}–{{ $absence['eligible_end_date'] }})</p>
                                    @endforeach
                                    <p class="mt-1 text-xs text-inkSoft">{{ $allocation->transaction?->description ?? 'Charge transaction' }}</p>
                                </td>
                                <td class="px-5 py-3 text-right font-mono">{{ $allocation->eligible_days === null ? '—' : number_format($allocation->eligible_days) }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ $allocation->approved_absence_days === null ? '—' : number_format($allocation->approved_absence_days) }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format($allocation->chargeable_days) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <p class="font-mono font-semibold">₱{{ number_format((float) $allocation->allocated_amount, 2) }}</p>
                                    @if (($allocation->rounding_adjustment_cents ?? 0) > 0)
                                        <p class="text-xs text-inkSoft">Rounding adjustment +{{ $allocation->rounding_adjustment_cents }} cent</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-ink/10 bg-paper/40">
                        <tr><th colspan="4" class="px-5 py-3 text-right">Allocated total</th><td class="px-5 py-3 text-right font-mono font-bold">₱{{ number_format((float) $allocatedTotal, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            <p class="border-t border-ink/8 px-5 py-3 text-sm font-semibold {{ $reconciles ? 'text-success' : 'text-red-700' }}" role="status">
                {{ $reconciles ? 'Tenant charges reconcile exactly with the room cost.' : 'Tenant charges do not reconcile with the room cost.' }}
            </p>
            @if ($bill->allocations->contains(fn ($allocation) => $allocation->eligible_days === null))
                <p class="border-t border-ink/8 px-5 py-3 text-xs text-inkSoft">This legacy bill predates occupancy-day audit snapshots.</p>
            @endif
        </section>
    </div>
</x-admin-layout>
