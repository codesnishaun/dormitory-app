<x-admin-layout>
    <x-slot:title>Electricity bills</x-slot:title>

    <div class="mx-auto w-full space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal">Submeter billing</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-ink">Electricity bills</h1>
                <p class="mt-2 text-sm text-inkSoft">Preview meter consumption and person-day allocations before finalizing charges.</p>
            </div>
            <a href="{{ route('admin.meter-assignments.index') }}" class="text-sm font-semibold text-teal hover:text-tealDeep">Manage meter assignments</a>
        </header>

        @if (session('success'))
            <div class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-ink/8 bg-paper3 p-5 shadow-sm sm:p-6" aria-labelledby="preview-electricity-heading">
            <h2 id="preview-electricity-heading" class="font-display text-xl font-bold text-ink">Prepare a billing cycle</h2>
            <p class="mt-1 text-sm text-inkSoft">Each room is billed separately. Meter readings are required at every assignment segment boundary.</p>

            <form method="POST" action="{{ route('admin.electricity-bills.preview') }}" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @csrf
                <label class="block text-sm font-medium text-ink">
                    Room
                    <select name="room_id" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                        <option value="">Select a room</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-ink">
                    Period start
                    <input type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                </label>
                <label class="block text-sm font-medium text-ink">
                    Period end
                    <input type="date" name="period_end" value="{{ old('period_end', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                </label>
                <label class="block text-sm font-medium text-ink">
                    Tariff (per kWh)
                    <input type="number" name="rate_per_kwh" value="{{ old('rate_per_kwh', '15.0615') }}" min="0.0001" max="999999.9999" step="0.0001" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                    <span class="mt-1 block text-xs font-normal text-inkSoft">Suggested rate: ₱15.0615/kWh. Editable each cycle.</span>
                </label>
                <div class="sm:col-span-2 xl:col-span-4">
                    <button type="submit" @disabled($rooms->isEmpty()) class="inline-flex h-11 items-center justify-center rounded-xl bg-teal px-5 text-sm font-semibold text-white transition hover:bg-tealDeep disabled:cursor-not-allowed disabled:opacity-50">Preview bill</button>
                    @if ($rooms->isEmpty())
                        <span class="ml-2 text-xs text-inkSoft">Add a room before preparing electricity bills.</span>
                    @endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-sm" aria-labelledby="finalized-bills-heading">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 id="finalized-bills-heading" class="font-display text-xl font-bold text-ink">Finalized bills</h2>
                <p class="mt-1 text-xs text-inkSoft">Finalized billing snapshots and charges cannot be edited or deleted.</p>
            </div>
            <div class="divide-y divide-ink/8">
                @forelse ($bills as $bill)
                    <a href="{{ route('admin.electricity-bills.show', $bill) }}" class="flex flex-col gap-2 px-5 py-4 transition hover:bg-paper/50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <span>
                            <span class="block text-sm font-semibold text-ink">{{ $bill->room->room_number }} · {{ $bill->period_start->format('M j, Y') }} – {{ $bill->period_end->format('M j, Y') }}</span>
                            <span class="mt-1 block text-xs text-inkSoft">{{ number_format((float) $bill->total_kwh, 3) }} kWh · ₱{{ number_format((float) $bill->rate_per_kwh, 4) }}/kWh · Finalized {{ $bill->finalized_at->format('M j, Y') }}</span>
                        </span>
                        <span class="flex items-center gap-3">
                            <span class="font-mono text-sm font-semibold text-ink">₱{{ number_format((float) $bill->total_amount, 2) }}</span>
                            <span class="text-xs font-semibold text-teal">View bill →</span>
                        </span>
                    </a>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-inkSoft">No electricity bills have been finalized.</p>
                @endforelse
            </div>
            @if ($bills->hasPages())
                <div class="border-t border-ink/8 px-5 py-4">{{ $bills->links() }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
