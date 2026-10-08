<x-admin-layout>
    <x-slot:title>Rooms &amp; Meters</x-slot:title>

    @php
        // Sample records are for the interface preview only; connect this list to room data later.
        $rooms = [
            ['number' => 'R101', 'floor' => 'Floor 1', 'type' => 'Single', 'rent' => 3500, 'tenants' => [['name' => 'Marianne Santos', 'initials' => 'MS']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 1219, 'previous' => 1182, 'usage' => 37, 'percent' => 62, 'rate' => 12, 'updated' => 'Oct 01, 2026'],
            ['number' => 'R102', 'floor' => 'Floor 1', 'type' => 'Single', 'rent' => 3500, 'tenants' => [['name' => 'Jomar Cruz', 'initials' => 'JC']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 990, 'previous' => 948, 'usage' => 42, 'percent' => 70, 'rate' => 12, 'updated' => 'Oct 01, 2026'],
            ['number' => 'R103', 'floor' => 'Floor 1', 'type' => 'Shared', 'rent' => 2800, 'tenants' => [['name' => 'Angeline Reyes', 'initials' => 'AR'], ['name' => 'Bea Villanueva', 'initials' => 'BV']], 'occupancy' => '2/2 occupied', 'status' => 'occupied', 'current' => 2265, 'previous' => 2210, 'usage' => 55, 'percent' => 92, 'rate' => 12, 'updated' => 'Oct 02, 2026'],
            ['number' => 'R104', 'floor' => 'Floor 1', 'type' => 'Shared', 'rent' => 2800, 'tenants' => [['name' => 'Carlo Mendoza', 'initials' => 'CM']], 'occupancy' => '1/2 occupied', 'status' => 'occupied', 'current' => 1780, 'previous' => 1750, 'usage' => 30, 'percent' => 50, 'rate' => 12, 'updated' => 'Oct 02, 2026'],
            ['number' => 'R201', 'floor' => 'Floor 2', 'type' => 'Single', 'rent' => 3800, 'tenants' => [['name' => 'Dianne Aquino', 'initials' => 'DA']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 905, 'previous' => 860, 'usage' => 45, 'percent' => 75, 'rate' => 12, 'updated' => 'Oct 03, 2026'],
            ['number' => 'R202', 'floor' => 'Floor 2', 'type' => 'Single', 'rent' => 3800, 'tenants' => [], 'occupancy' => 'Vacant', 'status' => 'vacant', 'current' => 1120, 'previous' => 1120, 'usage' => 0, 'percent' => 0, 'rate' => 12, 'updated' => 'Sep 01, 2026'],
            ['number' => 'R203', 'floor' => 'Floor 2', 'type' => 'Shared', 'rent' => 3000, 'tenants' => [['name' => 'Liza Mercado', 'initials' => 'LM']], 'occupancy' => '1/2 occupied', 'status' => 'reading_due', 'current' => 1540, 'previous' => 1502, 'usage' => 38, 'percent' => 64, 'rate' => 12, 'updated' => 'Sep 18, 2026'],
            ['number' => 'R204', 'floor' => 'Floor 2', 'type' => 'Single', 'rent' => 3800, 'tenants' => [['name' => 'Paolo Dela Cruz', 'initials' => 'PD']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 2024, 'previous' => 1979, 'usage' => 45, 'percent' => 75, 'rate' => 12, 'updated' => 'Oct 03, 2026'],
        ];

        $occupiedRooms = count(array_filter($rooms, fn ($room) => in_array($room['status'], ['occupied', 'reading_due'], true)));
        $vacantRooms = count(array_filter($rooms, fn ($room) => $room['status'] === 'vacant'));
        $readingsDue = count(array_filter($rooms, fn ($room) => $room['status'] === 'reading_due'));
    @endphp

    <div class="mx-auto w-full max-w-[1440px] space-y-6 sm:space-y-8">
        {{-- Page heading and note clarifying that the sample data is not saved. --}}
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-teal/15 bg-teal/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal"></span>
                    Property overview
                </div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Rooms &amp; meters</h1>
                <p class="mt-2 text-sm leading-6 text-inkSoft sm:text-base">
                    Keep occupancy in view and record each room's latest electricity reading.
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-2 rounded-xl border border-copper/15 bg-paper3 px-3.5 py-2.5 text-xs leading-5 text-inkSoft sm:max-w-xs">
                <svg class="h-4 w-4 shrink-0 text-copper" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 2a8 8 0 1 0 0 16 8 8 0 0 0 0-16Zm0 3a.75.75 0 0 1 .75.75v4a.75.75 0 0 1-1.5 0v-4A.75.75 0 0 1 10 5Zm0 8.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <span><strong class="font-semibold text-ink">Preview data.</strong> This screen is a front-end mockup; changes are not saved.</span>
            </div>
        </header>

        {{-- Compact totals give a quick overview before the room cards. --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4" aria-label="Room summary">
            <x-admin.room-stat title="Total rooms" :value="count($rooms)" description="Across 2 floors" tone="ink">
                <x-svg-icon name="rooms" class="h-4 w-4" />
            </x-admin.room-stat>
            <x-admin.room-stat title="Occupied" :value="$occupiedRooms" description="Rooms with residents" tone="success">
                <x-svg-icon name="tenants" class="h-4 w-4" />
            </x-admin.room-stat>
            <x-admin.room-stat title="Vacant" :value="$vacantRooms" description="Ready for a new tenant" tone="copper">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 17V5.5L10 2l7 3.5V17M7 17v-5h6v5M6 7h.01M10 7h.01M14 7h.01" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </x-admin.room-stat>
            <x-admin.room-stat title="Reading due" :value="$readingsDue" description="Meter needs an update" tone="danger">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <circle cx="10" cy="10" r="7.25" />
                    <path d="M10 5.75v4.5l2.75 1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </x-admin.room-stat>
        </section>

        <section class="space-y-4" aria-labelledby="room-list-heading">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="room-list-heading" class="font-display text-xl font-bold text-ink">All rooms</h2>
                    <p class="mt-1 text-xs text-inkSoft">{{ count($rooms) }} rooms</p>
                </div>
                <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <label class="relative block min-w-0 flex-1 sm:w-64">
                        <span class="sr-only">Search rooms or tenants</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-inkSoft/70" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <circle cx="8.75" cy="8.75" r="5.75" />
                            <path d="m13 13 4 4" stroke-linecap="round" />
                        </svg>
                        <input type="search" placeholder="Search rooms or tenants" class="h-11 w-full rounded-xl border border-ink/10 bg-paper3 pl-9 pr-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
                    </label>
                    <button type="button" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-teal px-4 text-sm font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M8 2.5v11M2.5 8h11" stroke-linecap="round" />
                        </svg>
                        Add room
                    </button>
                    <div class="flex gap-1 overflow-x-auto rounded-xl border border-ink/8 bg-paper3 p-1" aria-label="Room filters">
                        <button type="button" class="shrink-0 rounded-lg bg-ink px-3 py-2 text-xs font-semibold text-paper">All</button>
                        <button type="button" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Occupied</button>
                        <button type="button" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Vacant</button>
                        <button type="button" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Reading due</button>
                    </div>
                </div>
            </div>

            {{-- Each room is a separate component to keep this responsive grid readable. --}}
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($rooms as $room)
                    <x-admin.room-card :room="$room" />
                @endforeach
            </div>
        </section>
    </div>
</x-admin-layout>
