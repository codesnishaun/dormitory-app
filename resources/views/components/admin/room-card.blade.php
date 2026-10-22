@props(['room'])

<article
    class="min-w-0 overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-[0_4px_18px_rgba(22,35,28,0.045)] transition duration-200 hover:-translate-y-0.5 hover:border-teal/20 hover:shadow-[0_12px_30px_rgba(22,35,28,0.09)]"
>
    <div class="p-4 sm:p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                    <h3 class="font-display text-xl font-bold tracking-tight text-ink">{{ $room->room_number }}</h3>
                </div>
            </div>

            @if ($room->status === 'occupied')
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-success/10 px-2.5 py-1.5 text-[11px] font-semibold text-success"><span class="h-1.5 w-1.5 rounded-full bg-success"></span>Occupied</span>
            @elseif ($room->status === 'reserved')
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-copper/12 px-2.5 py-1.5 text-[11px] font-semibold text-copperDeep"><span class="h-1.5 w-1.5 rounded-full bg-copper"></span>Reserved</span>
            @else
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-ink/7 px-2.5 py-1.5 text-[11px] font-semibold text-inkSoft"><span class="h-1.5 w-1.5 rounded-full bg-inkSoft/45"></span>{{ ucfirst(str_replace('_', ' ', $room->status)) }}</span>
            @endif
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-ink/6 pt-4 text-sm">
            <div>
                <dt class="text-xs text-inkSoft">Capacity</dt>
                <dd class="mt-1 font-semibold text-ink">{{ $room->capacity }} {{ (int) $room->capacity === 1 ? 'person' : 'people' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-inkSoft">Monthly rent</dt>
                <dd class="mt-1 font-semibold text-ink">₱{{ number_format((float) $room->monthly_rent, 2) }}</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-xs text-inkSoft">Submeter</dt>
                <dd class="mt-1 font-mono text-xs font-medium text-ink">{{ $room->currentMeterAssignment?->submeter?->meter_number ?? 'Not assigned' }}</dd>
            </div>
        </dl>

        <div class="mt-4 border-t border-ink/6 pt-4">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">
                Current residents ({{ $room->roomAssignments->count() }}/{{ $room->capacity }})
            </h4>

            @if ($room->roomAssignments->isEmpty())
                <p class="mt-2 text-sm text-inkSoft">No current residents recorded.</p>
            @else
                <ul class="mt-2 flex flex-col gap-2">
                    @foreach ($room->roomAssignments as $assignment)
                        @if ($assignment->user)
                            <li>
                                <button type="button" data-tenant-detail data-tenant-id="{{ $assignment->user->id }}"
                                    class="flex w-full items-center justify-between gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-teal/8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-ink">{{ $assignment->user->name }}</span>
                                        <span class="block truncate text-xs text-inkSoft">{{ $assignment->user->email }}</span>
                                    </span>
                                    <span class="shrink-0 text-xs font-semibold text-teal">View details</span>
                                </button>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>

       <div class="mt-3 flex justify-end gap-2 border-t border-ink/6 pt-3">
        <button type="button"
            onclick="openEditRoom(@js($room->only(['id', 'room_number', 'capacity', 'monthly_rent', 'submeter_number', 'status'])))"
            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-inkSoft transition hover:bg-teal/8 hover:text-teal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="m10.8 2.7 2.5 2.5M3 13l2.7-.5 7.6-7.6a1.8 1.8 0 0 0-2.5-2.5l-7.6 7.6L3 13Z" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Edit
        </button>
        <button type="button"
            onclick="openDeleteRoom({{ $room->id }}, @js($room->room_number))"
            class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold text-danger transition hover:bg-danger/8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
        <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <path d="M2.5 4.5h11M6 4.5V2.7h4v1.8m-6.2 0 .6 8.8h7.2l.6-8.8M6.5 7v3.5m3-3.5v3.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        Delete
        </button>
        </div>
    </div>
</article>
