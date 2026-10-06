@php
    $tenants = [
        ['name' => 'Marianne Santos', 'email' => 'marianne.santos@example.com', 'room' => 'R101', 'contact' => '0917 220 4481', 'moveIn' => '2025-11-02', 'balance' => '—'],
        ['name' => 'Jomar Cruz', 'email' => 'jomar.cruz@example.com', 'room' => 'R102', 'contact' => '0928 771 0093', 'moveIn' => '2026-01-15', 'balance' => '₱450.00'],
        ['name' => 'Angeline Reyes', 'email' => 'angeline.reyes@example.com', 'room' => 'R103', 'contact' => '0917 553 2210', 'moveIn' => '2025-06-10', 'balance' => '—'],
        ['name' => 'Bea Villanueva', 'email' => 'bea.villanueva@example.com', 'room' => 'R103', 'contact' => '0995 402 8871', 'moveIn' => '2026-02-20', 'balance' => '—'],
        ['name' => 'Carlo Mendoza', 'email' => 'carlo.mendoza@example.com', 'room' => 'R104', 'contact' => '0906 118 4432', 'moveIn' => '2025-09-01', 'balance' => '₱1,200.00'],
        ['name' => 'Dianne Aquino', 'email' => 'dianne.aquino@example.com', 'room' => 'R201', 'contact' => '0917 004 7723', 'moveIn' => '2026-03-05', 'balance' => '—'],
        ['name' => 'Ederick Bautista', 'email' => 'ederick.bautista@example.com', 'room' => 'R203', 'contact' => '0928 331 9905', 'moveIn' => '2025-12-12', 'balance' => '—'],
    ];

    $reviewedApplications = [
        ['name' => 'Miguel Torres', 'reviewedAt' => '2026-08-30', 'room' => 'R204', 'status' => 'Rejected', 'note' => 'Not specified.'],
        ['name' => 'Katrina Lopez', 'reviewedAt' => '2026-08-28', 'room' => 'R202', 'status' => 'Rejected', 'note' => 'Not specified.'],
        ['name' => 'Paolo Ramos', 'reviewedAt' => '2026-08-05', 'room' => 'R101', 'status' => 'Rejected', 'note' => 'Room was already occupied by the time of review.'],
    ];
@endphp

<x-admin-layout>
    <x-slot:title>Tenants - Admin - DORA</x-slot:title>

    <x-header
        title="Tenants"
        description="Manage resident profiles and room assignments."
    />

    <section aria-labelledby="applications-heading" class="mb-6 rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="applications-heading" class="font-display text-lg font-bold">Applications awaiting review</h2>
            <span class="rounded-full bg-paper2 px-3 py-1 text-xs font-semibold text-inkSoft">0 pending</span>
        </div>

        <div class="flex min-h-28 items-center justify-center py-5 text-center">
            <p class="text-sm text-inkSoft">No pending applications right now.</p>
        </div>

        <div>
            <h3 class="mb-3 font-display text-base font-bold">Recently reviewed</h3>
            <ul class="flex flex-col gap-3">
                @foreach ($reviewedApplications as $application)
                    <li class="rounded-xl border border-paper2/80 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h4 class="font-semibold">{{ $application['name'] }}</h4>
                                <p class="mt-0.5 text-xs text-inkSoft">
                                    <time datetime="{{ $application['reviewedAt'] }}">{{ $application['reviewedAt'] }}</time>
                                    <span aria-hidden="true"> · </span>{{ $application['room'] }}
                                </p>
                            </div>
                            <span class="rounded-full bg-danger/10 px-2.5 py-1 text-[11px] font-semibold text-danger">
                                {{ $application['status'] }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-inkSoft">{{ $application['note'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section aria-labelledby="tenant-list-heading" class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 id="tenant-list-heading" class="font-display text-lg font-bold">
                All tenants <span class="text-inkSoft">({{ count($tenants) }})</span>
            </h2>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-full bg-copper px-4 py-2 text-sm font-semibold text-white transition hover:bg-copperDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper"
            >
                <span aria-hidden="true">+</span>
                Add tenant
            </button>
        </div>

        <div class="-mx-1 hidden overflow-x-auto px-1 md:block">
            <table class="w-full min-w-[850px] border-collapse text-left">
                <thead>
                    <tr class="border-b border-paper2 text-[11px] font-semibold uppercase tracking-wider text-inkSoft">
                        <th scope="col" class="px-3 py-3">Name</th>
                        <th scope="col" class="px-3 py-3">Room</th>
                        <th scope="col" class="px-3 py-3">Contact</th>
                        <th scope="col" class="px-3 py-3">Move-in</th>
                        <th scope="col" class="px-3 py-3">Balance</th>
                        <th scope="col" class="px-3 py-3">Status</th>
                        <th scope="col" class="px-3 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-paper2/70 text-sm">
                    @foreach ($tenants as $tenant)
                        <tr>
                            <th scope="row" class="px-3 py-3.5 font-normal">
                                <span class="block font-semibold text-ink">{{ $tenant['name'] }}</span>
                                <span class="mt-0.5 block text-xs text-inkSoft">{{ $tenant['email'] }}</span>
                            </th>
                            <td class="whitespace-nowrap px-3 py-3.5">{{ $tenant['room'] }}</td>
                            <td class="px-3 py-3.5">{{ $tenant['contact'] }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5">{{ $tenant['moveIn'] }}</td>
                            <td class="whitespace-nowrap px-3 py-3.5 font-mono text-xs">{{ $tenant['balance'] }}</td>
                            <td class="px-3 py-3.5">
                                <span class="rounded-full bg-success/15 px-2.5 py-1 text-[11px] font-semibold text-success">Active</span>
                            </td>
                            <td class="px-3 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="rounded-full border border-paper2 px-3.5 py-1.5 text-xs font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Edit</button>
                                    <button type="button" class="rounded-full border border-danger/40 px-3.5 py-1.5 text-xs font-semibold text-danger transition hover:bg-danger/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">Remove</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <ul class="flex flex-col gap-3 md:hidden">
            @foreach ($tenants as $tenant)
                <li class="rounded-xl border border-paper2/80 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="font-semibold">{{ $tenant['name'] }}</h3>
                            <p class="mt-0.5 break-all text-xs text-inkSoft">{{ $tenant['email'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-success/15 px-2.5 py-1 text-[11px] font-semibold text-success">Active</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Room</dt>
                            <dd class="mt-0.5">{{ $tenant['room'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Contact</dt>
                            <dd class="mt-0.5">{{ $tenant['contact'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Move-in</dt>
                            <dd class="mt-0.5">{{ $tenant['moveIn'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Balance</dt>
                            <dd class="mt-0.5 font-mono text-xs">{{ $tenant['balance'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex gap-2 border-t border-paper2/70 pt-3">
                        <button type="button" class="flex-1 rounded-full border border-paper2 px-3 py-2 text-xs font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Edit</button>
                        <button type="button" class="flex-1 rounded-full border border-danger/40 px-3 py-2 text-xs font-semibold text-danger transition hover:bg-danger/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">Remove</button>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</x-admin-layout>
