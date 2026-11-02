<x-tenant-layout body-class="min-h-screen bg-paper font-sans text-ink">
    <x-slot:title>Dashboard - DORA</x-slot:title>

    <x-header
        :title="'Welcome, '.auth()->user()->name"
        description="Your room, account, and latest dormitory updates."
    />

    <section aria-label="Your account summary" class="mb-7 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('tenant.room-bill') }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Current room</h2>
            <p class="mt-2 font-display text-3xl font-semibold leading-tight">{{ $roomAssignment?->room?->room_number ?? 'Unassigned' }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">{{ $roomAssignment ? ucfirst($roomAssignment->status).' assignment' : 'Room details' }} <span aria-hidden="true">→</span></p>
        </a>
        <a href="{{ route('tenant.room-bill') }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Monthly rent</h2>
            <p class="mt-2 font-mono text-2xl font-semibold leading-tight">{{ $roomAssignment?->room ? '₱'.number_format((float) $roomAssignment->room->monthly_rent, 2) : '—' }}</p>
            <p class="mt-1.5 text-sm text-inkSoft">{{ $roomAssignment ? 'For your assigned room' : 'No active room assignment' }}</p>
        </a>
        <a href="{{ route('tenant.room-bill') }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Latest electricity bill</h2>
            @if ($latestElectricityBill)
                <p class="mt-2 font-mono text-2xl font-semibold leading-tight">₱{{ number_format((float) $latestElectricityBill->allocations->sum('allocated_amount'), 2) }}</p>
                <p class="mt-1.5 text-sm text-inkSoft">{{ $latestElectricityBill->period_start->format('M j') }}–{{ $latestElectricityBill->period_end->format('M j, Y') }} <span aria-hidden="true">→</span></p>
            @else
                <p class="mt-2 font-display text-2xl font-semibold leading-tight">Not available</p>
                <p class="mt-1.5 text-sm text-inkSoft">No finalized bill for your account</p>
            @endif
        </a>
        <a href="{{ route('tenant.room-bill') }}" class="group rounded-2xl border border-paper2 bg-paper3 p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Outstanding balance</h2>
            <p class="mt-2 font-mono text-2xl font-semibold leading-tight">₱{{ number_format(max($balance, 0), 2) }}</p>
            <p class="mt-1.5 text-sm {{ $balance > 0 ? 'font-medium text-copperDeep' : 'font-medium text-success' }}">{{ $balance > 0 ? 'Balance due' : 'Account is settled' }} <span aria-hidden="true">→</span></p>
        </a>
    </section>

    <nav aria-label="Quick actions" class="mb-7 rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
        <h2 class="font-display text-lg font-bold">Quick actions</h2>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
            <a href="{{ route('tenant.absence-request') }}" class="rounded-xl border border-paper2 bg-white px-4 py-3 text-center text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Submit an absence request</a>
            <a href="{{ route('tenant.maintenance') }}" class="rounded-xl border border-paper2 bg-white px-4 py-3 text-center text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Report a maintenance concern</a>
            <a href="{{ route('tenant.room-bill') }}" class="rounded-xl border border-paper2 bg-white px-4 py-3 text-center text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">View billing details</a>
            <a href="{{ route('tenant.room-bill') }}#account-history-heading" class="rounded-xl border border-paper2 bg-white px-4 py-3 text-center text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">View payment history</a>
            <a href="{{ route('tenant.announcements') }}" class="rounded-xl border border-paper2 bg-white px-4 py-3 text-center text-sm font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Read announcements</a>
        </div>
    </nav>

    <section class="grid items-start gap-5 xl:grid-cols-2">
        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="absence-status-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 id="absence-status-heading" class="font-display text-lg font-bold">Absence requests</h2>
                    <p class="mt-1 text-sm text-inkSoft">Your latest request statuses</p>
                </div>
                <a href="{{ route('tenant.absence-request') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">View all</a>
            </div>
            <ul class="flex flex-col gap-2">
                @forelse ($absenceRequests as $absence)
                    <li class="flex flex-col gap-2 rounded-xl border border-paper2/80 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            <span class="block text-sm font-semibold">{{ $absence->start_date->format('M j') }}–{{ $absence->end_date->format('M j, Y') }}</span>
                            <span class="mt-1 block text-xs text-inkSoft">{{ $absence->reference }}</span>
                        </span>
                        <span class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold capitalize {{ $absence->status === 'approved' ? 'bg-success/10 text-success' : ($absence->status === 'rejected' ? 'bg-danger/10 text-danger' : 'bg-copper/15 text-copperDeep') }}">Status: {{ $absence->status }}</span>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">You have not submitted an absence request.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="maintenance-status-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 id="maintenance-status-heading" class="font-display text-lg font-bold">Maintenance requests</h2>
                    <p class="mt-1 text-sm text-inkSoft">Track the concerns you reported</p>
                </div>
                <a href="{{ route('tenant.maintenance') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">View all</a>
            </div>
            <ul class="flex flex-col gap-2">
                @forelse ($maintenanceRequests as $maintenance)
                    <li class="flex flex-col gap-2 rounded-xl border border-paper2/80 p-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ $maintenance->title }}</span>
                            <span class="mt-1 block text-xs text-inkSoft">{{ $maintenance->reference }} · {{ $maintenance->created_at->format('M j, Y') }}</span>
                        </span>
                        <span class="w-fit shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $maintenance->status === 'Resolved' ? 'bg-success/10 text-success' : ($maintenance->status === 'In Progress' ? 'bg-teal/10 text-tealDeep' : 'bg-copper/15 text-copperDeep') }}">Status: {{ $maintenance->status }}</span>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">You have not reported a maintenance concern.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="payment-history-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 id="payment-history-heading" class="font-display text-lg font-bold">Recent payments</h2>
                    <p class="mt-1 text-sm text-inkSoft">Your latest recorded account payments</p>
                </div>
                <a href="{{ route('tenant.room-bill') }}#account-history-heading" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Payment history</a>
            </div>
            <ul class="divide-y divide-paper2/80">
                @forelse ($recentPayments as $payment)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ $payment->description ?: 'Payment' }}</span>
                            <span class="mt-1 block text-xs text-inkSoft">{{ $payment->transaction_date->format('M j, Y') }}@if ($payment->payment_method) · {{ str_replace('_', ' ', ucfirst($payment->payment_method)) }}@endif</span>
                        </span>
                        <span class="shrink-0 font-mono text-sm font-semibold text-success">₱{{ number_format((float) $payment->amount, 2) }}</span>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">No payments have been recorded yet.</li>
                @endforelse
            </ul>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6" aria-labelledby="tenant-announcements-heading">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 id="tenant-announcements-heading" class="font-display text-lg font-bold">Recent announcements</h2>
                <a href="{{ route('tenant.announcements') }}" class="shrink-0 text-sm font-semibold text-teal underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Read all</a>
            </div>
            <ul class="flex flex-col gap-3">
                @forelse ($announcements as $announcement)
                    <li class="rounded-xl border border-paper2/80 p-4">
                        <h3 class="font-semibold">{{ $announcement->title }}</h3>
                        <p class="mt-1 text-xs text-inkSoft">{{ $announcement->publish_at->format('M j, Y') }} · {{ $announcement->category }}@if ($announcement->is_pinned) · Pinned @endif</p>
                        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-inkSoft">{{ $announcement->body }}</p>
                    </li>
                @empty
                    <li class="rounded-xl bg-paper px-3 py-6 text-center text-sm text-inkSoft">There are no current announcements.</li>
                @endforelse
            </ul>
        </article>
    </section>
</x-tenant-layout>
