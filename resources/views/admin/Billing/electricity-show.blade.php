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
            <div class="rounded-xl border border-ink/8 bg-paper3 p-4"><p class="text-xs text-inkSoft">Tariff snapshot</p><p class="mt-1 font-mono text-xl font-bold">₱{{ number_format((float) $bill->rate_per_kwh, 4) }}/kWh</p></div>
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
            <div class="border-b border-ink/8 px-5 py-4"><h2 id="bill-allocations-heading" class="font-display text-lg font-bold">Tenant charges</h2></div>
            <div class="divide-y divide-ink/8">
                @foreach ($bill->allocations as $allocation)
                    <div class="flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="text-sm font-semibold">{{ $allocation->tenant_name }}</p><p class="text-xs text-inkSoft">{{ number_format($allocation->chargeable_days) }} chargeable days · {{ $allocation->transaction?->description ?? 'Charge transaction' }}</p></div>
                        <p class="font-mono text-sm font-semibold">₱{{ number_format((float) $allocation->allocated_amount, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-admin-layout>
