<x-admin-layout>
    <x-slot:title>Preview electricity bill</x-slot:title>

    <div class="mx-auto w-full max-w-5xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal">Review before finalizing</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-ink">Bill preview · {{ $preview['room']->room_number }}</h1>
                <p class="mt-2 text-sm text-inkSoft">{{ $preview['period_start']->format('M j, Y') }} through {{ $preview['period_end']->format('M j, Y') }}</p>
            </div>
            <a href="{{ route('admin.electricity-bills.index') }}" class="text-sm font-semibold text-teal hover:text-tealDeep">Back to bills</a>
        </header>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4"><p class="text-xs text-inkSoft">Usage</p><p class="mt-1 font-mono text-xl font-bold">{{ number_format($preview['total_kwh'], 3) }} kWh</p></div>
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4">
                <p class="text-xs text-inkSoft">Cost source</p>
                <p class="mt-1 text-sm font-bold">{{ $preview['cost_source'] === 'provider_bill' ? 'Actual room utility-bill amount' : 'Explicit tariff estimate' }}</p>
                @if ($preview['rate_per_kwh'] !== null)
                    <p class="mt-1 font-mono text-xs text-inkSoft">₱{{ number_format((float) $preview['rate_per_kwh'], 4) }}/kWh</p>
                @endif
            </div>
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4"><p class="text-xs text-inkSoft">Person-days</p><p class="mt-1 font-mono text-xl font-bold">{{ number_format($preview['total_person_days']) }}</p></div>
            <div class="rounded-xl border border-teal/20 bg-teal/5 p-4"><p class="text-xs text-inkSoft">Total bill</p><p class="mt-1 font-mono text-xl font-bold text-teal">₱{{ number_format($preview['total_amount'], 2) }}</p></div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3" aria-labelledby="meter-segments-heading">
            <div class="border-b border-ink/8 px-5 py-4">
                <h2 id="meter-segments-heading" class="font-display text-lg font-bold">Meter usage</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[42rem] text-left text-sm">
                    <thead class="bg-paper/60 text-[10px] uppercase tracking-wider text-inkSoft">
                        <tr><th class="px-5 py-3">Meter</th><th class="px-5 py-3">Dates</th><th class="px-5 py-3">Opening</th><th class="px-5 py-3">Closing</th><th class="px-5 py-3 text-right">Usage</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink/8">
                        @foreach ($preview['segments'] as $segment)
                            <tr>
                                <td class="px-5 py-3 font-semibold">{{ $segment['meter_number'] }}</td>
                                <td class="px-5 py-3 text-xs text-inkSoft">{{ $segment['segment_start'] }} – {{ $segment['segment_end'] }}</td>
                                <td class="px-5 py-3 font-mono">{{ number_format((float) $segment['opening_reading'], 3) }}</td>
                                <td class="px-5 py-3 font-mono">{{ number_format((float) $segment['closing_reading'], 3) }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format((float) $segment['consumption_kwh'], 3) }} kWh</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3" aria-labelledby="tenant-allocations-heading">
            <div class="border-b border-ink/8 px-5 py-4">
                <h2 id="tenant-allocations-heading" class="font-display text-lg font-bold">Tenant allocation</h2>
                <p class="mt-1 text-xs text-inkSoft">Room cost × tenant chargeable days ÷ total chargeable person-days. Approved overlapping absence dates are deducted once.</p>
                <p class="mt-1 text-xs text-inkSoft">Allocation method: {{ $preview['allocation_method'] }}; leftover cents go to the largest fractional remainders, with assignment ID as the tie-break.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-paper/60 text-[10px] uppercase tracking-wider text-inkSoft">
                        <tr><th class="px-5 py-3">Tenant / assignment</th><th class="px-5 py-3 text-right">Eligible days</th><th class="px-5 py-3 text-right">Approved absence days</th><th class="px-5 py-3 text-right">Chargeable days</th><th class="px-5 py-3 text-right">Electricity charge</th></tr>
                    </thead>
                    <tbody class="divide-y divide-ink/8">
                        @foreach ($preview['allocations'] as $allocation)
                            <tr>
                                <td class="px-5 py-3">
                                    <p class="font-semibold">{{ $allocation['tenant_name'] }}</p>
                                    <p class="text-xs text-inkSoft">{{ $allocation['assignment_start_date'] }} – {{ $allocation['assignment_end_date'] ?? 'Ongoing' }}</p>
                                    @foreach ($allocation['absence_snapshots'] as $absence)
                                        <p class="mt-1 text-xs text-inkSoft">Approved absence #{{ $absence['absence_request_id'] }}: {{ $absence['requested_start_date'] }}–{{ $absence['requested_end_date'] }} (eligible overlap {{ $absence['eligible_start_date'] }}–{{ $absence['eligible_end_date'] }})</p>
                                    @endforeach
                                </td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format($allocation['eligible_days']) }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format($allocation['approved_absence_days']) }}</td>
                                <td class="px-5 py-3 text-right font-mono">{{ number_format($allocation['chargeable_days']) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <p class="font-mono font-semibold">₱{{ number_format((float) $allocation['allocated_amount'], 2) }}</p>
                                    @if ($allocation['rounding_adjustment_cents'] > 0)
                                        <p class="text-xs text-inkSoft">Rounding adjustment +{{ $allocation['rounding_adjustment_cents'] }} cent</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-ink/10 bg-paper/40">
                        <tr><th colspan="4" class="px-5 py-3 text-right">Allocated total</th><td class="px-5 py-3 text-right font-mono font-bold">₱{{ number_format((float) $preview['allocated_total'], 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            <p class="border-t border-ink/8 px-5 py-3 text-sm font-semibold {{ $preview['reconciles'] ? 'text-success' : 'text-red-700' }}" role="status">
                {{ $preview['reconciles'] ? 'Tenant charges reconcile exactly with the room cost.' : 'Tenant charges do not reconcile with the room cost; do not finalize.' }}
            </p>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('admin.electricity-bills.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-ink/10 px-5 text-sm font-semibold text-ink">Cancel</a>
            <form method="POST" action="{{ route('admin.electricity-bills.finalize') }}">
                @csrf
                <input type="hidden" name="room_id" value="{{ $preview['room']->id }}">
                <input type="hidden" name="period_start" value="{{ $preview['period_start']->toDateString() }}">
                <input type="hidden" name="period_end" value="{{ $preview['period_end']->toDateString() }}">
                <input type="hidden" name="rate_per_kwh" value="{{ $preview['rate_per_kwh'] ?? '' }}">
                <input type="hidden" name="actual_cost" value="{{ $preview['cost_source'] === 'provider_bill' ? $preview['total_amount'] : '' }}">
                <input type="hidden" name="preview_token" value="{{ $token }}">
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-teal px-5 text-sm font-semibold text-white transition hover:bg-tealDeep sm:w-auto">Finalize bill and create charges</button>
            </form>
        </div>
    </div>
</x-admin-layout>
