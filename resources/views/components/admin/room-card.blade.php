{{-- Room identity, resident list, and current occupancy status. --}}
@props(['room'])

<article
    class="min-w-0 overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-[0_4px_18px_rgba(22,35,28,0.045)] transition duration-200 hover:-translate-y-0.5 hover:border-teal/20 hover:shadow-[0_12px_30px_rgba(22,35,28,0.09)]"
>
    <div class="p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                    <h3 class="font-display text-xl font-bold tracking-tight text-ink">{{ $room['number'] }}</h3>
                    <span class="rounded-md bg-ink/5 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-inkSoft">{{ $room['floor'] }}</span>
                </div>
                <p class="mt-1 text-xs text-inkSoft">{{ $room['type'] }} room <span class="px-1 text-inkSoft/40">/</span> <span class="font-semibold text-ink">₱{{ number_format($room['rent']) }}</span><span class="text-inkSoft"> / month</span></p>
            </div>

            @if ($room['status'] === 'vacant')
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-ink/7 px-2.5 py-1.5 text-[11px] font-semibold text-inkSoft"><span class="h-1.5 w-1.5 rounded-full bg-inkSoft/45"></span>Vacant</span>
            @elseif ($room['status'] === 'reading_due')
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-copper/12 px-2.5 py-1.5 text-[11px] font-semibold text-copperDeep"><span class="h-1.5 w-1.5 rounded-full bg-copper"></span>Reading due</span>
            @else
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-success/10 px-2.5 py-1.5 text-[11px] font-semibold text-success"><span class="h-1.5 w-1.5 rounded-full bg-success"></span>Occupied</span>
            @endif
        </div>

        <div class="mt-4 min-h-9">
            @if (count($room['tenants']) > 0)
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($room['tenants'] as $tenant)
                        <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-paper2 bg-paper px-2.5 py-1 text-[11px] font-medium text-ink">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal/12 text-[9px] font-bold text-teal">{{ $tenant['initials'] }}</span>
                            <span class="truncate">{{ $tenant['name'] }}</span>
                        </span>
                    @endforeach
                </div>
            @else
                <p class="inline-flex items-center gap-2 rounded-lg bg-ink/4 px-3 py-2 text-xs text-inkSoft">
                    <span class="h-1.5 w-1.5 rounded-full bg-copper"></span>
                    No current tenant
                </p>
            @endif
        </div>

        <x-admin.room-meter :room="$room" />

        <div class="mt-3 flex justify-end gap-2 border-t border-ink/6 pt-3">
            <button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-inkSoft transition hover:bg-teal/8 hover:text-teal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="m10.8 2.7 2.5 2.5M3 13l2.7-.5 7.6-7.6a1.8 1.8 0 0 0-2.5-2.5l-7.6 7.6L3 13Z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Edit
            </button>
            <button type="button" class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-danger transition hover:bg-danger/8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M2.5 4.5h11M6 4.5V2.7h4v1.8m-6.2 0 .6 8.8h7.2l.6-8.8M6.5 7v3.5m3-3.5v3.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Delete
            </button>
        </div>
    </div>
</article>
