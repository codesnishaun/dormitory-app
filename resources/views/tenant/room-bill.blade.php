<x-tenant-layout body-class="min-h-screen bg-paper font-sans text-ink">
    <x-slot:title>My room &amp; bill - DORA</x-slot:title>

    <div class="mx-auto w-full space-y-6">
        <header>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal">Tenant account</p>
            <h1 class="mt-2 font-display text-3xl font-bold">My room and bill</h1>
            <p class="mt-1 text-sm text-inkSoft">Your room assignment, finalized electricity bills, and account transactions.</p>
        </header>

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(18rem,0.7fr)]">
            <div class="rounded-2xl border border-ink/8 bg-paper3 p-5 shadow-sm">
                <h2 class="font-display text-xl font-bold">Room details</h2>
                @if ($roomAssignment)
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div><dt class="text-xs text-inkSoft">Room</dt><dd class="mt-1 text-lg font-semibold">{{ $roomAssignment->room->room_number }}</dd></div>
                        <div><dt class="text-xs text-inkSoft">Assignment status</dt><dd class="mt-1 text-sm font-semibold capitalize">{{ $roomAssignment->status }}</dd></div>
                        <div><dt class="text-xs text-inkSoft">Monthly rent</dt><dd class="mt-1 font-mono text-sm font-semibold">₱{{ number_format((float) $roomAssignment->room->monthly_rent, 2) }}</dd></div>
                        <div><dt class="text-xs text-inkSoft">Move-in date</dt><dd class="mt-1 text-sm">{{ $roomAssignment->start_date->format('M j, Y') }}</dd></div>
                    </dl>
                @else
                    <p class="mt-3 rounded-xl bg-paper2 p-4 text-sm text-inkSoft">You do not have a current room assignment.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-ink/8 bg-paper3 p-5 shadow-sm">
                <h2 class="font-display text-xl font-bold">Account balance</h2>
                <p class="mt-4 font-mono text-3xl font-bold {{ $balance > 0 ? 'text-copperDeep' : 'text-success' }}">₱{{ number_format(abs($balance), 2) }}</p>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div><dt class="text-xs text-inkSoft">Charges</dt><dd class="mt-1 font-mono font-semibold">₱{{ number_format($chargesTotal, 2) }}</dd></div>
                    <div><dt class="text-xs text-inkSoft">Payments</dt><dd class="mt-1 font-mono font-semibold">₱{{ number_format($paymentsTotal, 2) }}</dd></div>
                </dl>
                <p class="mt-3 text-xs text-inkSoft">{{ $balance < 0 ? 'Credit balance' : 'Balance due' }} includes all charges and payments recorded on your account.</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-sm" aria-labelledby="electricity-cycles-heading">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 id="electricity-cycles-heading" class="font-display text-xl font-bold">Electricity bill history</h2>
                <p class="mt-1 text-xs text-inkSoft">Only finalized bills and their saved reading snapshots appear here.</p>
            </div>
            <div class="divide-y divide-ink/8">
                @forelse ($allocations as $allocation)
                    @php($bill = $allocation->electricityBill)
                    <article class="space-y-4 px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-sm font-semibold">{{ $bill->room->room_number }} · {{ $bill->period_start->format('M j, Y') }} – {{ $bill->period_end->format('M j, Y') }}</h3>
                                <p class="mt-1 text-xs text-inkSoft">
                                    {{ number_format((float) $bill->total_kwh, 3) }} kWh ·
                                    {{ $bill->cost_source === 'provider_bill' ? 'Actual room utility-bill cost' : 'Estimated at ₱'.number_format((float) $bill->rate_per_kwh, 4).'/kWh' }}
                                </p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="font-mono text-lg font-bold text-teal">₱{{ number_format((float) $allocation->allocated_amount, 2) }}</p>
                                <p class="text-xs text-inkSoft">{{ number_format($allocation->chargeable_days) }} chargeable days</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[32rem] text-left text-xs">
                                <thead class="text-[10px] uppercase tracking-wider text-inkSoft"><tr><th class="py-2 pr-3">Meter</th><th class="py-2 pr-3">Reading dates</th><th class="py-2 pr-3">Opening</th><th class="py-2 pr-3">Closing</th><th class="py-2 text-right">Usage</th></tr></thead>
                                <tbody class="divide-y divide-ink/5">
                                    @foreach ($bill->segments as $segment)
                                        <tr>
                                            <td class="py-2 pr-3 font-semibold">{{ $segment->meterAssignment->submeter->meter_number }}</td>
                                            <td class="py-2 pr-3 text-inkSoft">{{ $segment->segment_start->format('Y-m-d') }} – {{ $segment->segment_end->format('Y-m-d') }}</td>
                                            <td class="py-2 pr-3 font-mono">{{ number_format((float) $segment->opening_reading, 3) }}</td>
                                            <td class="py-2 pr-3 font-mono">{{ number_format((float) $segment->closing_reading, 3) }}</td>
                                            <td class="py-2 text-right font-mono">{{ number_format((float) $segment->consumption_kwh, 3) }} kWh</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-inkSoft">No finalized electricity bills are available yet.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-sm" aria-labelledby="account-history-heading">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 id="account-history-heading" class="font-display text-xl font-bold">Account transaction history</h2>
            </div>
            <div class="divide-y divide-ink/8">
                @forelse ($transactions as $transaction)
                    <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <p class="text-sm font-semibold">{{ $transaction->description ?: ucfirst($transaction->type) }}</p>
                            <p class="mt-1 text-xs text-inkSoft">{{ $transaction->transaction_date->format('M j, Y') }} · {{ ucfirst($transaction->type) }}@if ($transaction->due_date) · Due {{ $transaction->due_date->format('M j, Y') }}@endif</p>
                        </div>
                        <p class="font-mono text-sm font-semibold {{ $transaction->type === 'payment' ? 'text-success' : 'text-ink' }}">
                            {{ $transaction->type === 'payment' ? '−' : '' }}₱{{ number_format((float) $transaction->amount, 2) }}
                        </p>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-inkSoft">No account transactions have been recorded.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-tenant-layout>
