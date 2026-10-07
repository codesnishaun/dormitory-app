<x-admin-layout body-class="h-dvh overflow-hidden bg-paper font-sans text-ink">
    <x-slot:title>
        Admin Dashboard
    </x-slot:title>

    <x-header
        title="Overview"
        description="A snapshot of Cozy Haven right now."
    />

    <section aria-label="Dormitory overview" class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Occupied rooms', 'value' => '6/8', 'note' => '2 vacant', 'noteClass' => 'text-success'],
            ['label' => 'Active tenants', 'value' => '7', 'note' => 'Across 6 rooms', 'noteClass' => 'text-success'],
            ['label' => 'Pending maintenance', 'value' => '2', 'note' => 'Needs attention', 'noteClass' => 'text-danger'],
            ['label' => 'This cycle usage', 'value' => '267 kWh', 'note' => '₱3,204.00 total', 'noteClass' => 'text-success'],
        ] as $metric)
            <article class="rounded-2xl border border-paper2 bg-paper3 p-5">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">{{ $metric['label'] }}</h2>
                <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $metric['value'] }}</p>
                <p class="mt-1.5 text-sm font-medium {{ $metric['noteClass'] }}">{{ $metric['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-label="Recent activity and announcements" class="grid items-stretch gap-5 lg:grid-cols-[1.2fr_1fr]">
        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-bold">Recent maintenance requests</h2>
                <a
                    href="{{ route('admin.maintenance.index') }}"
                    class="shrink-0 rounded-full border border-paper2 px-4 py-1.5 text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                >View all</a>
            </div>

            <ul class="flex flex-col gap-3">
                @foreach ([
                    ['title' => 'Aircon not cooling', 'details' => 'R201 · Dianne Aquino · 2026-08-27', 'status' => 'Pending', 'statusClass' => 'bg-copper/15 text-copperDeep'],
                    ['title' => 'Leaking faucet in shared bathroom', 'details' => 'R104 · Carlo Mendoza · 2026-08-25', 'status' => 'In Progress', 'statusClass' => 'bg-teal/15 text-tealDeep'],
                    ['title' => 'Loose door hinge', 'details' => 'R103 · Angeline Reyes · 2026-08-10', 'status' => 'Resolved', 'statusClass' => 'bg-success/15 text-success'],
                ] as $request)
                    <li class="flex items-start justify-between gap-3 rounded-xl border border-paper2/80 px-4 py-3.5">
                        <div class="min-w-0">
                            <h3 class="font-semibold leading-snug">{{ $request['title'] }}</h3>
                            <p class="mt-1 text-xs text-inkSoft">{{ $request['details'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $request['statusClass'] }}">
                            {{ $request['status'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="font-display text-lg font-bold">Pinned announcements</h2>
                <a
                    href="{{ route('admin.announcement.index') }}"
                    class="shrink-0 rounded-full border border-paper2 px-4 py-1.5 text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                >Manage</a>
            </div>

            <div class="rounded-xl border border-copper bg-copper/5 p-4">
                <h3 class="font-display text-base font-bold">Scheduled water interruption — Aug 30</h3>
                <time datetime="2026-08-27" class="mt-1 block text-xs text-inkSoft">2026-08-27</time>
            </div>

            <p class="mt-2.5 rounded-xl bg-paper2 px-4 py-3 text-sm text-inkSoft">
                <span class="font-semibold text-ink">₱1,650.00</span> in outstanding tenant balances.
            </p>
        </article>
    </section>
</x-admin-layout>