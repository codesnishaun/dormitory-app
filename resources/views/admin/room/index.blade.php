<x-admin-layout>
    <x-slot:title>Rooms &amp; Meters</x-slot:title>

   @php
        // Sample rooms (preview data) stay as they were.
        $sampleRooms = [
            ['number' => 'R101', 'floor' => 'Floor 1', 'type' => 'Single', 'rent' => 3500, 'tenants' => [['name' => 'Marianne Santos', 'initials' => 'MS']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 1219, 'previous' => 1182, 'usage' => 37, 'percent' => 62, 'rate' => 12, 'updated' => 'Oct 01, 2026'],
            ['number' => 'R102', 'floor' => 'Floor 1', 'type' => 'Single', 'rent' => 3500, 'tenants' => [['name' => 'Jomar Cruz', 'initials' => 'JC']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 990, 'previous' => 948, 'usage' => 42, 'percent' => 70, 'rate' => 12, 'updated' => 'Oct 01, 2026'],
            ['number' => 'R103', 'floor' => 'Floor 1', 'type' => 'Shared', 'rent' => 2800, 'tenants' => [['name' => 'Angeline Reyes', 'initials' => 'AR'], ['name' => 'Bea Villanueva', 'initials' => 'BV']], 'occupancy' => '2/2 occupied', 'status' => 'occupied', 'current' => 2265, 'previous' => 2210, 'usage' => 55, 'percent' => 92, 'rate' => 12, 'updated' => 'Oct 02, 2026'],
            ['number' => 'R104', 'floor' => 'Floor 1', 'type' => 'Shared', 'rent' => 2800, 'tenants' => [['name' => 'Carlo Mendoza', 'initials' => 'CM']], 'occupancy' => '1/2 occupied', 'status' => 'occupied', 'current' => 1780, 'previous' => 1750, 'usage' => 30, 'percent' => 50, 'rate' => 12, 'updated' => 'Oct 02, 2026'],
            ['number' => 'R201', 'floor' => 'Floor 2', 'type' => 'Single', 'rent' => 3800, 'tenants' => [['name' => 'Dianne Aquino', 'initials' => 'DA']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 905, 'previous' => 860, 'usage' => 45, 'percent' => 75, 'rate' => 12, 'updated' => 'Oct 03, 2026'],
            ['number' => 'R202', 'floor' => 'Floor 2', 'type' => 'Single', 'rent' => 3800, 'tenants' => [], 'occupancy' => 'Vacant', 'status' => 'vacant', 'current' => 1120, 'previous' => 1120, 'usage' => 0, 'percent' => 0, 'rate' => 12, 'updated' => 'Sep 01, 2026'],
            ['number' => 'R203', 'floor' => 'Floor 2', 'type' => 'Shared', 'rent' => 3000, 'tenants' => [['name' => 'Liza Mercado', 'initials' => 'LM']], 'occupancy' => '1/2 occupied', 'status' => 'reading_due', 'current' => 1540, 'previous' => 1502, 'usage' => 38, 'percent' => 64, 'rate' => 12, 'updated' => 'Sep 18, 2026'],
            ['number' => 'R204', 'floor' => 'Floor 2', 'type' => 'Single', 'rent' => 3800, 'tenants' => [['name' => 'Paolo Dela Cruz', 'initials' => 'PD']], 'occupancy' => '1/1 occupied', 'status' => 'occupied', 'current' => 2024, 'previous' => 1979, 'usage' => 45, 'percent' => 75, 'rate' => 12, 'updated' => 'Oct 03, 2026'],
        ];

        // Sample rooms first, then rooms added through the modal (added rooms go at the end).
        // If an added room has the same number as a sample room, the saved one replaces it.
        $rooms = collect($sampleRooms)->keyBy('number')
            ->merge(collect($savedRooms)->keyBy('number'))
            ->values()
            ->all();

        $occupiedRooms = count(array_filter($rooms, fn ($room) => in_array($room['status'], ['occupied', 'reading_due'], true)));
        $vacantRooms = count(array_filter($rooms, fn ($room) => $room['status'] === 'vacant'));
        $readingsDue = count(array_filter($rooms, fn ($room) => $room['status'] === 'reading_due'));
        $floorCount = collect($rooms)->pluck('floor')->unique()->count();
    @endphp

    <div class="mx-auto w-full max-w-[1440px] space-y-6 sm:space-y-8">
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
                <span><strong class="font-semibold text-ink">Rooms are saved.</strong> Tenant names and meter readings are still preview data.</span>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4" aria-label="Room summary">
            <x-admin.room-stat title="Total rooms" :value="count($rooms)" :description="'Across ' . $floorCount . ' floors'" tone="ink">
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
                        <input id="room-search" type="search" placeholder="Search rooms or tenants" class="h-11 w-full rounded-xl border border-ink/10 bg-paper3 pl-9 pr-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
                    </label>
                    <button type="button" onclick="document.getElementById('add-room-modal').showModal()" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-teal px-4 text-sm font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M8 2.5v11M2.5 8h11" stroke-linecap="round" />
                        </svg>
                        Add room
                    </button>
                    <div class="flex gap-1 overflow-x-auto rounded-xl border border-ink/8 bg-paper3 p-1" aria-label="Room filters">
                        <button type="button" data-filter="all" class="shrink-0 rounded-lg bg-ink px-3 py-2 text-xs font-semibold text-paper">All</button>
                        <button type="button" data-filter="occupied" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Occupied</button>
                        <button type="button" data-filter="vacant" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Vacant</button>
                        <button type="button" data-filter="reading-due" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Reading due</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($rooms as $room)
                    <div data-room-card
                         data-status="{{ $room['status'] }}"
                         data-search="{{ strtolower($room['number'] . ' ' . collect($room['tenants'])->pluck('name')->implode(' ')) }}"
                         class="h-full [&>*]:h-full">
                        <x-admin.room-card :room="$room" />
                    </div>
                @endforeach
            </div>

            <p id="room-empty" class="hidden py-10 text-center text-sm text-inkSoft">No rooms match your filter.</p>
        </section>
    </div>

    {{-- Add room modal --}}
    <dialog id="add-room-modal"
        style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 28rem; max-height: 90vh; overflow-y: auto;"
        class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
        <h2 class="font-display text-xl font-bold text-ink">Add room</h2>

        @if ($errors->any())
            <div class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.room.store') }}" class="mt-4 flex flex-col gap-4">
            @csrf

            <label class="text-sm font-semibold text-ink">Room number
                <input name="room_number" value="{{ old('room_number') }}" required placeholder="e.g. R205"
                       class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
            </label>

            <div class="grid grid-cols-2 gap-4">
                <label class="text-sm font-semibold text-ink">Floor
                    <input type="number" name="floor_number" value="{{ old('floor_number') }}" min="1" required
                           class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
                </label>
                <label class="text-sm font-semibold text-ink">Capacity
                    <input type="number" name="capacity" value="{{ old('capacity', 1) }}" min="1" required
                           class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
                </label>
            </div>

            <label class="text-sm font-semibold text-ink">Monthly rent (₱)
                <input type="number" step="0.01" name="monthly_rent" value="{{ old('monthly_rent') }}" min="0" required
                       class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
            </label>

            <label class="text-sm font-semibold text-ink">Submeter number (optional)
                <input name="submeter_number" value="{{ old('submeter_number') }}"
                       class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
            </label>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="this.closest('dialog').close()"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft">Cancel</button>
                <button type="submit" class="rounded-xl bg-teal px-4 py-2 text-sm font-semibold text-white hover:bg-tealDeep">Save room</button>
            </div>
        </form>
    </dialog>

    @if ($errors->any())
        <script>document.getElementById('add-room-modal').showModal();</script>
    @endif

    {{-- Filter tabs + search --}}
    <script>
        (function () {
            const cards  = document.querySelectorAll('[data-room-card]');
            const tabs   = document.querySelectorAll('[data-filter]');
            const search = document.getElementById('room-search');
            const empty  = document.getElementById('room-empty');
            const groups = {
                'all': null,
                'occupied': ['occupied', 'reading_due'],
                'vacant': ['vacant'],
                'reading-due': ['reading_due'],
            };
            let active = 'all';

            function apply() {
                const q = search.value.trim().toLowerCase();
                let shown = 0;

                cards.forEach(card => {
                    const allowed  = groups[active];
                    const okTab    = !allowed || allowed.includes(card.dataset.status);
                    const okSearch = !q || card.dataset.search.includes(q);
                    const show = okTab && okSearch;
                    card.classList.toggle('hidden', !show);
                    if (show) shown++;
                });

                empty.classList.toggle('hidden', shown > 0);

                tabs.forEach(tab => {
                    const on = tab.dataset.filter === active;
                    tab.classList.toggle('bg-ink', on);
                    tab.classList.toggle('text-paper', on);
                    tab.classList.toggle('text-inkSoft', !on);
                });
            }

            tabs.forEach(tab => tab.addEventListener('click', () => { active = tab.dataset.filter; apply(); }));
            search.addEventListener('input', apply);
            apply();
        })();
    </script>
</x-admin-layout>