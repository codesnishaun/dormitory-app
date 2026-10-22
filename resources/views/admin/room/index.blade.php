@include('admin.room.create')
@include('admin.room.edit')
@include('admin.room.delete')

<x-admin-layout>
    <x-slot:title>Rooms &amp; Meters</x-slot:title>

    <div class="mx-auto w-full space-y-6 sm:space-y-8">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-teal/15 bg-teal/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal"></span>
                    Property overview
                </div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Rooms &amp; meters</h1>
                <p class="mt-2 text-sm leading-6 text-inkSoft sm:text-base">
                    Review room availability, capacity, rent, and meter details.
                </p>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif
        
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4" aria-label="Room summary">
            <x-admin.room-stat title="Total rooms" :value="$rooms->count()" description="Current inventory" tone="ink">
                <x-svg-icon name="rooms" class="h-4 w-4" />
            </x-admin.room-stat>

            <x-admin.room-stat title="Occupied" :value="$occupiedRooms" description="Marked as occupied" tone="success">
                <x-svg-icon name="tenants" class="h-4 w-4" />
            </x-admin.room-stat>

            <x-admin.room-stat title="Available" :value="$availableRooms" description="Ready for applications" tone="copper">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 17V5.5L10 2l7 3.5V17M7 17v-5h6v5M6 7h.01M10 7h.01M14 7h.01" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </x-admin.room-stat>

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
                         data-search="{{ strtolower($room->room_number.' '.$room->roomAssignments->pluck('user.name')->filter()->implode(' ').' '.$room->roomAssignments->pluck('user.email')->filter()->implode(' ')) }}"
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

    <dialog id="tenant-details-modal"
        style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 36rem; max-height: 90vh; overflow-y: auto;"
        aria-labelledby="tenant-details-heading"
        class="rounded-2xl bg-paper3 p-0 text-ink shadow-2xl backdrop:bg-ink/50">
        <div class="flex items-start justify-between gap-4 border-b border-paper2 px-5 py-4 sm:px-6">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-teal">Tenant profile</p>
                <h2 id="tenant-details-heading" class="mt-1 font-display text-xl font-bold">Tenant details</h2>
            </div>
            <button type="button" aria-label="Close tenant details" onclick="this.closest('dialog').close()"
                class="rounded-lg p-2 text-inkSoft transition hover:bg-paper2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="space-y-5 p-5 sm:p-6">
            <section aria-labelledby="tenant-contact-heading">
                <h3 id="tenant-contact-heading" class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Contact and account</h3>
                <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><dt class="text-xs text-inkSoft">Name</dt><dd id="tenant-details-name" class="mt-1 break-words text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Email</dt><dd id="tenant-details-email" class="mt-1 break-all text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Phone</dt><dd id="tenant-details-phone" class="mt-1 text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Account</dt><dd id="tenant-details-account" class="mt-1 text-sm font-semibold"></dd></div>
                </dl>
            </section>

            <section aria-labelledby="tenant-stay-heading" class="border-t border-paper2 pt-4">
                <h3 id="tenant-stay-heading" class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Current stay</h3>
                <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div><dt class="text-xs text-inkSoft">Room</dt><dd id="tenant-details-room" class="mt-1 text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Assignment</dt><dd id="tenant-details-status" class="mt-1 text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Start date</dt><dd id="tenant-details-start" class="mt-1 text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">End date</dt><dd id="tenant-details-end" class="mt-1 text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Monthly rent</dt><dd id="tenant-details-rent" class="mt-1 text-sm font-semibold"></dd></div>
                    <div><dt class="text-xs text-inkSoft">Submeter</dt><dd id="tenant-details-submeter" class="mt-1 text-sm font-semibold"></dd></div>
                </dl>
            </section>

            <section aria-labelledby="tenant-history-heading" class="border-t border-paper2 pt-4">
                <h3 id="tenant-history-heading" class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Room assignment history</h3>
                <ul id="tenant-details-history" class="mt-3 flex flex-col gap-2 text-sm"></ul>
            </section>
        </div>
    </dialog>

    {{-- Filter tabs + search --}}
    <script>
        const tenantDetails = @js($tenantDetails);

        document.querySelectorAll('[data-tenant-detail]').forEach(button => {
            button.addEventListener('click', () => {
                const tenant = tenantDetails[button.dataset.tenantId];
                if (!tenant) {
                    return;
                }

                document.getElementById('tenant-details-name').textContent = tenant.name;
                document.getElementById('tenant-details-email').textContent = tenant.email;
                document.getElementById('tenant-details-phone').textContent = tenant.phone;
                document.getElementById('tenant-details-account').textContent = tenant.accountStatus;
                document.getElementById('tenant-details-room').textContent = tenant.room;
                document.getElementById('tenant-details-status').textContent = tenant.assignmentStatus;
                document.getElementById('tenant-details-start').textContent = tenant.startDate;
                document.getElementById('tenant-details-end').textContent = tenant.endDate;
                document.getElementById('tenant-details-rent').textContent = `₱${tenant.monthlyRent}`;
                document.getElementById('tenant-details-submeter').textContent = tenant.submeter;

                const history = document.getElementById('tenant-details-history');
                history.replaceChildren();

                tenant.roomHistory.forEach(assignment => {
                    const item = document.createElement('li');
                    item.className = 'rounded-lg bg-paper px-3 py-2';
                    item.textContent = `${assignment.room} · ${assignment.status} · ${assignment.startDate} – ${assignment.endDate}`;
                    history.append(item);
                });

                document.getElementById('tenant-details-modal').showModal();
            });
        });

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