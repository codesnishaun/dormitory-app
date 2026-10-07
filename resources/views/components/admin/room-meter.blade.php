{{-- Meter details, temporary reading input, and a small recent-reading preview. --}}
@props(['room'])

@php
    $electricBill = $room['usage'] * $room['rate'];
@endphp

<div class="mt-4 rounded-xl border border-paper2/80 bg-paper/75 p-3 sm:p-4">
    <div class="flex items-center gap-3">
        <div style="background: conic-gradient(#2F6F6E {{ $room['percent'] }}%, #DCE4D7 0);" class="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-full">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-paper3 font-mono text-[10px] font-semibold text-ink">
                {{ $room['percent'] }}%
            </div>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline justify-between gap-x-2 gap-y-0.5">
                <p class="font-mono text-sm font-semibold text-ink">{{ $room['usage'] }} kWh <span class="font-sans text-xs font-medium text-inkSoft">used</span></p>
                <p class="text-[10px] text-inkSoft">{{ $room['occupancy'] }}</p>
            </div>
            <p class="mt-1 text-[11px] leading-4 text-inkSoft">
                Previous {{ number_format($room['previous']) }}
                <span class="px-0.5 text-inkSoft/50">/</span>
                Current {{ number_format($room['current']) }} kWh
            </p>
            <p class="mt-0.5 text-[11px] leading-4 text-inkSoft">
                Bill <span class="font-semibold text-ink">₱{{ number_format($electricBill, 2) }}</span>
                <span class="px-0.5 text-inkSoft/50">/</span>
                ₱{{ number_format($room['rate'], 2) }} per kWh
            </p>
        </div>
    </div>
    <div class="mt-3 flex items-center justify-between border-t border-paper2/80 pt-2.5 text-[10px] text-inkSoft">
        <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <circle cx="8" cy="8" r="5.75" />
                <path d="M8 4.5v3.75l2.25 1.25" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span>{{ $room['updated'] }}</span>
        </span>
        <span class="font-medium uppercase tracking-wider">Electric meter</span>
    </div>
</div>

<div class="mt-3 flex flex-col gap-2">
    <div class="flex min-w-0 flex-1 gap-2">
        <label class="min-w-0 flex-1">
            <span class="sr-only">New meter reading for {{ $room['number'] }} in kWh</span>
            <input type="number" min="{{ $room['current'] }}" step="any" inputmode="decimal" placeholder="New reading (kWh)" class="h-10 w-full min-w-0 rounded-lg border border-ink/10 bg-white px-3 text-xs font-medium text-ink outline-none transition placeholder:font-normal placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
        </label>
        <button type="button" class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal px-3 text-xs font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M8 3v10M3 8h10" stroke-linecap="round" />
            </svg>
            Record
        </button>
    </div>
    <details>
        <summary class="flex h-10 w-full cursor-pointer list-none items-center justify-between gap-2 rounded-lg border border-ink/10 bg-paper3 px-3 text-xs font-semibold text-inkSoft transition hover:border-ink/20 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal [&::-webkit-details-marker]:hidden">
            <span class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <path d="M2.5 8s2-4.25 5.5-4.25S13.5 8 13.5 8s-2 4.25-5.5 4.25S2.5 8 2.5 8Z" />
                <circle cx="8" cy="8" r="1.5" />
            </svg>
            History
            </span>
            <span class="text-[10px] font-medium text-inkSoft/70">Recent meter readings</span>
        </summary>
        <div class="mt-2 overflow-x-auto rounded-xl border border-ink/10 bg-white/70 p-3">
            <div class="grid min-w-[240px] grid-cols-[1fr_auto] gap-x-4 gap-y-2 text-xs">
                <span class="font-medium text-inkSoft">Reading date</span>
                <span class="text-right font-medium text-inkSoft">Meter value</span>
                <span class="text-inkSoft">{{ $room['updated'] }}</span>
                <span class="text-right font-mono font-semibold text-ink">{{ number_format($room['current']) }} kWh</span>
                <span class="text-inkSoft">Previous reading</span>
                <span class="text-right font-mono font-medium text-ink">{{ number_format($room['previous']) }} kWh</span>
            </div>
        </div>
    </details>
</div>
