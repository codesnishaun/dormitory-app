 @include('admin.room.create')
 
<x-admin-layout>
    <x-slot:title>Rooms &amp; Meters</x-slot:title>

    <div class="mx-auto w-full max-w-[1440px] space-y-6 sm:space-y-8">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-teal/15 bg-teal/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal"></span>
                    Property overview
                </div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Rooms &amp; meters</h1>
                <p class="mt-2 text-sm leading-6 text-inkSoft sm:text-base">
                    Review room availability, capacity, rent, and meter details.
                    Review room availability, capacity, rent, and meter details.
                </p>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif
        
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4" aria-label="Room summary">
            <x-admin.room-stat title="Total rooms" :value="$rooms->count()" description="Current inventory" tone="ink">
            <x-admin.room-stat title="Total rooms" :value="$rooms->count()" description="Current inventory" tone="ink">
                <x-svg-icon name="rooms" class="h-4 w-4" />
            </x-admin.room-stat>
            <x-admin.room-stat title="Occupied" :value="$occupiedRooms" description="Marked as occupied" tone="success">
            <x-admin.room-stat title="Occupied" :value="$occupiedRooms" description="Marked as occupied" tone="success">
                <x-svg-icon name="tenants" class="h-4 w-4" />
            </x-admin.room-stat>
            <x-admin.room-stat title="Available" :value="$availableRooms" description="Ready for applications" tone="copper">
            <x-admin.room-stat title="Available" :value="$availableRooms" description="Ready for applications" tone="copper">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 17V5.5L10 2l7 3.5V17M7 17v-5h6v5M6 7h.01M10 7h.01M14 7h.01" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </x-admin.room-stat>
            <x-admin.room-stat title="Reserved" :value="$reservedRooms" description="Marked as reserved" tone="copper">
            <x-admin.room-stat title="Reserved" :value="$reservedRooms" description="Marked as reserved" tone="copper">
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
                    <p class="mt-1 text-xs text-inkSoft">{{ $rooms->count() }} rooms</p>
                    <p class="mt-1 text-xs text-inkSoft">{{ $rooms->count() }} rooms</p>
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
                        <button type="button" data-filter="available" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Available</button>
                        <button type="button" data-filter="reserved" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-inkSoft transition hover:bg-ink/5">Reserved</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($rooms as $room)
                    <div data-room-card
                         data-status="{{ $room->status }}"
                         data-search="{{ strtolower($room->room_number) }}"
                         class="h-full [&>*]:h-full">
                        <x-admin.room-card :room="$room" />
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-ink/15 bg-paper3 p-6 text-center text-sm text-inkSoft md:col-span-2 xl:col-span-3">
                        No rooms have been added yet.
                    </p>
                @endforelse
            </div>

            <p id="room-empty" class="hidden py-10 text-center text-sm text-inkSoft">No rooms match your filter.</p>
        </section>
    </div>

    {{-- Filter tabs + search --}}
    <script>
        (function () {
            const cards  = document.querySelectorAll('[data-room-card]');
            const tabs   = document.querySelectorAll('[data-filter]');
            const search = document.getElementById('room-search');
            const empty  = document.getElementById('room-empty');
            let active = 'all';

            function apply() {
                const q = search.value.trim().toLowerCase();
                let shown = 0;

                cards.forEach(card => {
                    const okTab    = active === 'all' || card.dataset.status === active;
                    const okSearch = !q || card.dataset.search.includes(q);
                    const show = okTab && okSearch;
                    card.classList.toggle('hidden', !show);
                    if (show) shown++;
                });

                empty.classList.toggle('hidden', shown > 0 || cards.length === 0);

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