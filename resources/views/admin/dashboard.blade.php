<x-admin-layout body-class="h-dvh overflow-hidden bg-paper font-sans text-ink">
    <x-slot:title>Admin Dashboard - DORA</x-slot:title>

    <x-header
        title="Dormitory overview"
        description="A live snapshot of occupancy, resident requests, and billing."
    />

    <section aria-label="Dormitory statistics" class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.tenant.index') }}" aria-label="Active tenants: {{ $tenantCount }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Active tenants</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $tenantCount }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">Tenant accounts <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.room.index') }}" aria-label="Occupied rooms: {{ $occupiedRooms }} of {{ $totalRooms }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Occupied rooms</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $occupiedRooms }}<span class="text-lg text-inkSoft"> / {{ $totalRooms }}</span></p>
            <p class="mt-1.5 text-sm text-inkSoft">{{ $occupancyRate }}% room occupancy <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.room.index') }}" aria-label="Available rooms: {{ $availableRooms }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Available rooms</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $availableRooms }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">{{ $reservedRooms }} reserved or other status <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.tenant.index') }}#applications-heading" aria-label="Pending applications: {{ $pendingApplicationCount }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Pending applications</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $pendingApplicationCount }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">Awaiting review <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.absence-request.index', ['status' => 'pending']) }}" aria-label="Pending absence requests: {{ $pendingAbsenceCount }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Absence requests</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $pendingAbsenceCount }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">Pending approval <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.maintenance.index') }}" aria-label="Pending maintenance requests: {{ $pendingMaintenanceCount }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Maintenance requests</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $pendingMaintenanceCount }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">Pending attention <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.billing.index') }}" aria-label="Outstanding balances: ₱{{ number_format($outstandingBalance, 2) }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Outstanding balances</h2>
            <p class="mt-2 font-mono text-2xl font-semibold leading-tight">₱{{ number_format($outstandingBalance, 2) }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">Open tenant balances <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('admin.electricity-bills.index') }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Electricity billing</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $recentBills->count() }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">Recent finalized bills <span aria-hidden="true">→</span></p>
        </a>
    </section>

    <section aria-label="Pending actions" class="mb-7">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold">Needs your attention</h2>
                <p class="mt-1 text-sm text-inkSoft">Review the latest items waiting for an administrative action.</p>
            </div>
            <a href="{{ route('admin.tenant.index') }}" class="text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Manage residents and applications</a>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <article class="rounded-2xl border border-paper2 bg-paper3 p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="font-semibold">Applications</h3>
                    <span class="rounded-full bg-copper/15 px-2.5 py-1 text-xs font-semibold text-copperDeep">{{ $pendingApplicationCount }} pending</span>
                </div>
                <ul class="flex flex-col gap-2">
                    @forelse ($pendingApplications as $application)
                        <li>
                            <a href="{{ route('admin.tenant.index') }}#applications-heading" class="block rounded-xl border border-paper2/80 p-3 transition hover:bg-paper/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                <span class="block text-sm font-semibold">{{ $application->applicant_name }}</span>
                                <span class="mt-1 block text-xs text-inkSoft">Pending review · Move-in {{ $application->move_in_date }} · {{ $application->preferredRoom?->room_number ?? 'No room preference' }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-paper px-3 py-5 text-center text-sm text-inkSoft">No applications are waiting for review.</li>
                    @endforelse
                </ul>
            </article>

            <article class="rounded-2xl border border-paper2 bg-paper3 p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="font-semibold">Absence requests</h3>
                    <span class="rounded-full bg-copper/15 px-2.5 py-1 text-xs font-semibold text-copperDeep">{{ $pendingAbsenceCount }} pending</span>
                </div>
                <ul class="flex flex-col gap-2">
                    @forelse ($pendingAbsences as $absence)
                        <li>
                            <a href="{{ route('admin.absence-request.index', ['status' => 'pending']) }}" class="block rounded-xl border border-paper2/80 p-3 transition hover:bg-paper/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold">{{ $absence->roomAssignment?->user?->name ?? 'Tenant unavailable' }}</span>
                                    <span class="shrink-0 font-mono text-[11px] text-inkSoft">{{ $absence->reference }}</span>
                                </span>
                                <span class="mt-1 block text-xs text-inkSoft">{{ $absence->start_date->format('M j') }}–{{ $absence->end_date->format('M j, Y') }} · Room {{ $absence->roomAssignment?->room?->room_number ?? '—' }}</span>
                                <span class="mt-2 inline-flex rounded-full bg-copper/15 px-2.5 py-1 text-[11px] font-semibold text-copperDeep">Pending approval</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-paper px-3 py-5 text-center text-sm text-inkSoft">No absence requests are waiting for approval.</li>
                    @endforelse
                </ul>
            </article>

            <article class="rounded-2xl border border-paper2 bg-paper3 p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="font-semibold">Maintenance</h3>
                    <span class="rounded-full bg-copper/15 px-2.5 py-1 text-xs font-semibold text-copperDeep">{{ $pendingMaintenanceCount }} pending</span>
                </div>
                <ul class="flex flex-col gap-2">
                    @forelse ($pendingMaintenance as $maintenance)
                        <li>
                            <a href="{{ route('admin.maintenance.index') }}" class="block rounded-xl border border-paper2/80 p-3 transition hover:bg-paper/70 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-semibold">{{ $maintenance->title }}</span>
                                    <span class="shrink-0 font-mono text-[11px] text-inkSoft">{{ $maintenance->reference }}</span>
                                </span>
                                <span class="mt-1 block text-xs text-inkSoft">{{ $maintenance->user?->name ?? 'Tenant unavailable' }} · Room {{ $maintenance->roomAssignment?->room?->room_number ?? '—' }}</span>
                                <span class="mt-2 inline-flex rounded-full bg-copper/15 px-2.5 py-1 text-[11px] font-semibold text-copperDeep">Pending</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-paper px-3 py-5 text-center text-sm text-inkSoft">No maintenance requests are awaiting attention.</li>
                    @endforelse
                </ul>
            </article>
        </div>
    </section>

    <section class="grid items-start gap-5 xl:grid-cols-2">
        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="room-overview-heading">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 id="room-overview-heading" class="font-display text-lg font-bold">Room overview</h2>
                    <p class="mt-1 text-sm text-inkSoft">{{ $totalRooms }} rooms in the dormitory</p>
                </div>
                <a href="{{ route('admin.room.index') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Manage rooms</a>
            </div>
            <div class="mt-5" role="img" aria-label="{{ $occupiedRooms }} of {{ $totalRooms }} rooms occupied, {{ $availableRooms }} available, and {{ $reservedRooms }} reserved or other status">
                <div class="mb-2 flex items-center justify-between text-xs font-semibold text-inkSoft">
                    <span>Occupancy</span>
                    <span>{{ $occupancyRate }}%</span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-paper2">
                    <div class="h-full rounded-full bg-teal transition-all" style="width: {{ $occupancyRate }}%"></div>
                </div>
            </div>
            <dl class="mt-5 grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-paper px-3 py-3"><dt class="text-xs text-inkSoft">Occupied</dt><dd class="mt-1 font-display text-xl font-bold">{{ $occupiedRooms }}</dd></div>
                <div class="rounded-xl bg-paper px-3 py-3"><dt class="text-xs text-inkSoft">Available</dt><dd class="mt-1 font-display text-xl font-bold">{{ $availableRooms }}</dd></div>
                <div class="rounded-xl bg-paper px-3 py-3"><dt class="text-xs text-inkSoft">Reserved / other</dt><dd class="mt-1 font-display text-xl font-bold">{{ $reservedRooms }}</dd></div>
            </dl>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="electricity-activity-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 id="electricity-activity-heading" class="font-display text-lg font-bold">Recent electricity billing</h2>
                    <p class="mt-1 text-sm text-inkSoft">Finalized periods and saved readings</p>
                </div>
                <a href="{{ route('admin.electricity-bills.index') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">View bills</a>
            </div>
            <ul class="divide-y divide-paper2/80">
                @forelse ($recentBills as $bill)
                    <li>
                        <a href="{{ route('admin.electricity-bills.show', $bill) }}" class="flex flex-col gap-1 py-3 transition hover:bg-paper/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{ $bill->room?->room_number ?? 'Room unavailable' }} · {{ $bill->period_start->format('M j') }}–{{ $bill->period_end->format('M j, Y') }}</span>
                                <span class="mt-1 block text-xs text-inkSoft">{{ number_format((float) $bill->total_kwh, 3) }} kWh</span>
                            </span>
                            <span class="font-mono text-sm font-semibold">₱{{ number_format((float) $bill->total_amount, 2) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">No finalized electricity bills yet.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="account-activity-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 id="account-activity-heading" class="font-display text-lg font-bold">Recent account activity</h2>
                    <p class="mt-1 text-sm text-inkSoft">Latest charges and payments recorded</p>
                </div>
                <a href="{{ route('admin.billing.index') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Open billing</a>
            </div>
            <ul class="divide-y divide-paper2/80">
                @forelse ($recentTransactions as $transaction)
                    <li class="flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">{{ $transaction->description ?: ucfirst($transaction->type) }} · {{ $transaction->user?->name ?? 'Tenant unavailable' }}</span>
                            <span class="mt-1 block text-xs text-inkSoft">{{ $transaction->transaction_date->format('M j, Y') }} · {{ ucfirst($transaction->type) }}</span>
                        </span>
                        <span class="font-mono text-sm font-semibold {{ $transaction->type === 'payment' ? 'text-success' : 'text-ink' }}">{{ $transaction->type === 'payment' ? '−' : '' }}₱{{ number_format((float) $transaction->amount, 2) }}</span>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">No account transactions have been recorded.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="announcements-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="announcements-heading" class="font-display text-lg font-bold">Published announcements</h2>
                <a href="{{ route('admin.announcement.index') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Manage</a>
            </div>
            <ul class="flex flex-col gap-3">
                @forelse ($announcements as $announcement)
                    <li class="rounded-xl border border-paper2/80 p-4">
                        <h3 class="font-semibold">{{ $announcement->title }}</h3>
                        <p class="mt-1 text-xs text-inkSoft">{{ $announcement->publish_at->format('M j, Y') }} · {{ $announcement->category }}@if ($announcement->is_pinned) · Pinned @endif</p>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">No announcements are currently published.</li>
                @endforelse
            </ul>
        </article>
    </section>
</x-admin-layout>
