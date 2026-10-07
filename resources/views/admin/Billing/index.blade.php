<x-admin-layout>
    <x-slot:title>Billing</x-slot:title>

    @php
        // Static preview records for the billing dashboard; these are not connected to saved transactions.
        $tenantBalances = [
            ['name' => 'Marianne Santos', 'room' => 'R101', 'balance' => 0, 'date' => '2026-08-01', 'activity' => 'Payment', 'amount' => 3920],
            ['name' => 'Jomar Cruz', 'room' => 'R102', 'balance' => 450, 'date' => '2026-08-02', 'activity' => 'Payment', 'amount' => 3500],
            ['name' => 'Angeline Reyes', 'room' => 'R103', 'balance' => 0, 'date' => '2026-08-02', 'activity' => 'Payment', 'amount' => 2172],
            ['name' => 'Bea Villanueva', 'room' => 'R103', 'balance' => 0, 'date' => '2026-08-02', 'activity' => 'Payment', 'amount' => 2172],
            ['name' => 'Carlo Mendoza', 'room' => 'R104', 'balance' => 1200, 'date' => '2026-07-28', 'activity' => 'Charge', 'amount' => 1200],
            ['name' => 'Dianne Aquino', 'room' => 'R201', 'balance' => 0, 'date' => '2026-08-03', 'activity' => 'Payment', 'amount' => 4304],
            ['name' => 'Ederick Bautista', 'room' => 'R203', 'balance' => 0, 'date' => '2026-08-03', 'activity' => 'Payment', 'amount' => 3636],
        ];

        $recentTransactions = [
            ['date' => '2026-08-03', 'tenant' => 'Dianne Aquino', 'room' => 'R201', 'type' => 'Payment', 'amount' => 4304, 'note' => 'August rent + electric'],
            ['date' => '2026-08-03', 'tenant' => 'Ederick Bautista', 'room' => 'R203', 'type' => 'Payment', 'amount' => 3636, 'note' => 'August rent + electric'],
            ['date' => '2026-08-02', 'tenant' => 'Jomar Cruz', 'room' => 'R102', 'type' => 'Payment', 'amount' => 3500, 'note' => 'August rent payment'],
            ['date' => '2026-07-28', 'tenant' => 'Carlo Mendoza', 'room' => 'R104', 'type' => 'Charge', 'amount' => 1200, 'note' => 'Water and shared utilities'],
        ];

        $outstandingBalance = array_sum(array_column($tenantBalances, 'balance'));
        $tenantsInGoodStanding = count(array_filter($tenantBalances, fn ($tenant) => $tenant['balance'] === 0));
    @endphp

    <div class="mx-auto w-full max-w-[1440px] space-y-6 pb-4 sm:space-y-8">
        {{-- Billing overview and quick context. --}}
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-teal/15 bg-teal/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal"></span>
                    Accounts overview
                </div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Billing</h1>
                <p class="mt-2 text-sm leading-6 text-inkSoft sm:text-base">Track balances, payments, and charges per tenant.</p>
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-ink/8 bg-paper3 px-3.5 py-2.5 text-xs text-inkSoft">
                <svg class="h-4 w-4 shrink-0 text-teal" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M5 3.5h10M5 7h10M5 10.5h6M4 16.5h12a1 1 0 0 0 1-1v-11a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1Z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span><span class="font-semibold text-ink">August 2026</span> billing period</span>
            </div>
        </header>

        {{-- Reuse the room overview stat component for a consistent admin dashboard. --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4" aria-label="Billing summary">
            <x-admin.room-stat
                title="Outstanding balance"
                :value="'₱'.number_format($outstandingBalance, 2)"
                :description="count(array_filter($tenantBalances, fn ($tenant) => $tenant['balance'] > 0)).' tenants owing'"
                tone="danger"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M10 2.75v14.5m4-10.75c-.55-.92-1.65-1.5-3.25-1.5h-1.5a2.75 2.75 0 0 0 0 5.5h1.5a2.75 2.75 0 0 1 0 5.5h-2c-1.6 0-2.7-.58-3.25-1.5" stroke-linecap="round" />
                </svg>
            </x-admin.room-stat>
            <x-admin.room-stat title="Collected this month" value="₱0.00" description="Across 7 accounts" tone="success">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M4 10.5 8 14l8-8M3 3.5h14v13H3z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </x-admin.room-stat>
            <x-admin.room-stat title="Tenants in good standing" :value="$tenantsInGoodStanding.'/'.count($tenantBalances)" description="Zero balance" tone="success">
                <x-svg-icon name="tenants" class="h-4 w-4" />
            </x-admin.room-stat>
            <x-admin.room-stat title="Total transactions logged" value="7" description="All-time" tone="ink">
                <x-svg-icon name="billing" class="h-4 w-4" />
            </x-admin.room-stat>
        </section>

        {{-- Balance list changes from a compact data table to stacked rows on narrow screens. --}}
        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-[0_4px_18px_rgba(22,35,28,0.035)]" aria-labelledby="balances-heading">
            <div class="flex flex-col gap-3 border-b border-ink/8 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 id="balances-heading" class="font-display text-xl font-bold text-ink">Tenant balances</h2>
                    <p class="mt-1 text-xs text-inkSoft">Current account status and most recent activity.</p>
                </div>
                <label class="relative block w-full sm:w-64">
                    <span class="sr-only">Search tenants</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-inkSoft/70" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <circle cx="8.75" cy="8.75" r="5.75" />
                        <path d="m13 13 4 4" stroke-linecap="round" />
                    </svg>
                    <input type="search" placeholder="Search tenants" class="h-10 w-full rounded-lg border border-ink/10 bg-white/70 pl-9 pr-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
                </label>
            </div>

            <div data-billing-heading class="hidden gap-3 border-b border-ink/10 px-6 py-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-inkSoft min-[1200px]:grid min-[1200px]:grid-cols-[minmax(0,2.6fr)_minmax(48px,0.6fr)_minmax(95px,1.1fr)_minmax(180px,2.5fr)_minmax(285px,3.2fr)]">
                <span>Tenant</span>
                <span>Room</span>
                <span>Balance</span>
                <span>Last transaction</span>
                <span class="text-right">Actions</span>
            </div>

            <div class="divide-y divide-ink/6">
                @foreach ($tenantBalances as $tenant)
                    <article data-billing-row class="grid gap-3 px-4 py-4 transition hover:bg-paper/45 sm:px-6 min-[1200px]:grid-cols-[minmax(0,2.6fr)_minmax(48px,0.6fr)_minmax(95px,1.1fr)_minmax(180px,2.5fr)_minmax(285px,3.2fr)] min-[1200px]:items-center">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal/10 text-[11px] font-bold text-teal">
                                {{ collect(explode(' ', $tenant['name']))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-ink">{{ $tenant['name'] }}</span>
                                <span class="text-[11px] text-inkSoft min-[1200px]:hidden">{{ $tenant['room'] }}</span>
                            </span>
                        </div>

                        <div data-billing-room class="hidden text-sm text-inkSoft min-[1200px]:block">{{ $tenant['room'] }}</div>

                        <div data-billing-balance class="flex items-center justify-between gap-2 min-[1200px]:block">
                            <span data-billing-mobile-label class="text-[11px] font-medium text-inkSoft min-[1200px]:hidden">Balance</span>
                            @if ($tenant['balance'] > 0)
                                <span class="font-mono text-sm font-semibold text-danger">₱{{ number_format($tenant['balance'], 2) }}</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-success">
                                    <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                                    Settled
                                </span>
                            @endif
                        </div>

                        <div data-billing-activity class="flex items-center justify-between gap-2 text-xs text-inkSoft min-[1200px]:block">
                            <span data-billing-mobile-label class="text-[11px] font-medium text-inkSoft min-[1200px]:hidden">Last transaction</span>
                            <span class="text-right min-[1200px]:text-left">
                                <span class="block text-ink">{{ $tenant['date'] }}</span>
                                <span>{{ $tenant['activity'] }} · ₱{{ number_format($tenant['amount'], 2) }}</span>
                            </span>
                        </div>

                        <div class="flex min-w-0 flex-wrap gap-2 xl:flex-nowrap">
                            <button type="button" class="inline-flex h-9 min-w-0 flex-1 items-center justify-center whitespace-nowrap rounded-lg bg-teal px-2 text-[10px] font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                <span class="xl:hidden">Payment</span>
                                <span class="hidden xl:inline">Record payment</span>
                            </button>
                            <button type="button" class="inline-flex h-9 min-w-0 flex-1 items-center justify-center whitespace-nowrap rounded-lg border border-ink/12 bg-paper3 px-2 text-[10px] font-semibold text-ink transition hover:border-teal/40 hover:bg-teal/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                <span class="xl:hidden">Charge</span>
                                <span class="hidden xl:inline">Add charge</span>
                            </button>
                            <button type="button" class="inline-flex h-9 min-w-0 flex-1 items-center justify-center whitespace-nowrap rounded-lg border border-ink/12 bg-transparent px-2 text-[10px] font-semibold text-inkSoft transition hover:border-ink/25 hover:bg-ink/5 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                History
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="flex flex-col gap-2 border-t border-ink/8 bg-paper/35 px-4 py-3 text-xs text-inkSoft sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <span>Showing {{ count($tenantBalances) }} tenant accounts</span>
                <span>Balances reflect the sample preview data.</span>
            </div>
        </section>

        {{-- Recent transactions use the same data once in a separate chronological view. --}}
        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-[0_4px_18px_rgba(22,35,28,0.035)]" aria-labelledby="transactions-heading">
            <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-4 py-4 sm:px-6">
                <div>
                    <h2 id="transactions-heading" class="font-display text-xl font-bold text-ink">Recent transactions</h2>
                    <p class="mt-1 text-xs text-inkSoft">Latest payments and charges recorded.</p>
                </div>
                <button type="button" class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-teal transition hover:bg-teal/8">View all</button>
            </div>

            <div data-transaction-heading class="hidden gap-3 border-b border-ink/10 px-6 py-3 text-[10px] font-semibold uppercase tracking-[0.12em] text-inkSoft min-[1024px]:grid min-[1024px]:grid-cols-[minmax(0,2fr)_minmax(0,3fr)_minmax(0,2fr)_minmax(0,2fr)_minmax(0,3fr)]">
                <span>Date</span>
                <span>Tenant</span>
                <span>Type</span>
                <span>Amount</span>
                <span>Note</span>
            </div>

            <div class="divide-y divide-ink/6">
                @foreach ($recentTransactions as $transaction)
                    <article data-transaction-row class="grid gap-3 px-4 py-4 text-sm sm:px-6 min-[1024px]:grid-cols-[minmax(0,2fr)_minmax(0,3fr)_minmax(0,2fr)_minmax(0,2fr)_minmax(0,3fr)] min-[1024px]:items-center">
                        <time class="text-xs text-inkSoft">{{ $transaction['date'] }}</time>
                        <div class="min-w-0">
                            <span class="font-medium text-ink">{{ $transaction['tenant'] }}</span>
                            <span class="ml-1 text-xs text-inkSoft">({{ $transaction['room'] }})</span>
                        </div>
                        <span class="{{ $transaction['type'] === 'Payment' ? 'bg-success/10 text-success' : 'bg-copper/12 text-copperDeep' }} w-fit rounded-full px-2.5 py-1 text-[10px] font-semibold">{{ $transaction['type'] }}</span>
                        <span class="font-mono text-sm font-medium text-ink">₱{{ number_format($transaction['amount'], 2) }}</span>
                        <p class="text-xs leading-5 text-inkSoft">{{ $transaction['note'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </div>
</x-admin-layout>
