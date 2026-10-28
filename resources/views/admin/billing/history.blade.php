{{-- Transaction history modals (one per tenant) --}}
@foreach ($tenantBalances as $tenant)
    <dialog id="history-{{ $tenant['id'] }}"
        style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 34rem; max-height: 85vh; overflow-y: auto;"
        class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="font-display text-xl font-bold text-ink">{{ $tenant['name'] }}</h2>
                <p class="mt-1 text-xs text-inkSoft">
                    Room {{ $tenant['room'] }} · Balance ₱{{ number_format(max($tenant['balance'], 0), 2) }}
                </p>
            </div>
            <button type="button" onclick="this.closest('dialog').close()"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-inkSoft hover:bg-ink/5">Close</button>
        </div>

        @if (empty($tenant['history']))
            <p class="mt-8 mb-4 text-center text-sm text-inkSoft">No transactions yet.</p>
        @else
            <ul class="mt-4 divide-y divide-ink/8">
                @foreach ($tenant['history'] as $item)
                    <li class="flex items-start justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-ink">{{ $item['title'] }}</p>
                            <p class="text-xs text-inkSoft">{{ $item['date'] }}{{ $item['meta'] ? ' · ' . $item['meta'] : '' }}</p>
                        </div>
                        <span class="shrink-0 font-mono text-sm font-medium {{ $item['type'] === 'Payment' ? 'text-success' : 'text-copperDeep' }}">
                            {{ $item['type'] === 'Payment' ? '−' : '+' }}₱{{ number_format($item['amount'], 2) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </dialog>
@endforeach