<x-admin-layout>
    <x-slot:title>
        Maintenance
    </x-slot:title>

    <x-header
        title="Maintenance"
        description="Track repair requests from submission through resolution."
    />

    <section aria-label="Maintenance request summary" class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'All requests', 'value' => $summary['total'], 'detail' => 'In the maintenance log', 'tone' => 'text-teal'],
            ['label' => 'Pending', 'value' => $summary['pending'], 'detail' => 'Waiting for review', 'tone' => 'text-copperDeep'],
            ['label' => 'In progress', 'value' => $summary['in_progress'], 'detail' => 'Currently being handled', 'tone' => 'text-tealDeep'],
            ['label' => 'Resolved', 'value' => $summary['resolved'], 'detail' => 'Completed requests', 'tone' => 'text-success'],
        ] as $stat)
            <article class="rounded-2xl border border-paper2 bg-paper3 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-inkSoft">{{ $stat['label'] }}</p>
                        <p data-summary-count="{{ Str::slug($stat['label']) }}" class="mt-2 font-display text-3xl font-semibold leading-none text-ink">{{ $stat['value'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-paper2/70 {{ $stat['tone'] }}" aria-hidden="true">
                        <x-svg-icon name="maintenance" class="h-4.5 w-4.5" />
                    </span>
                </div>
                <p class="mt-3 text-xs text-inkSoft">{{ $stat['detail'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="requests-heading" class="overflow-hidden rounded-2xl border border-paper2 bg-paper3 shadow-sm shadow-ink/5">
        <div class="flex flex-col gap-4 border-b border-paper2/80 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 id="requests-heading" class="font-display text-xl font-bold text-ink">Maintenance requests</h2>
                    <span class="rounded-full bg-paper2/70 px-2.5 py-1 text-xs font-semibold text-inkSoft">
                        <span data-visible-count>{{ count($maintenanceRequests) }}</span> total
                    </span>
                </div>
                <p class="mt-1 text-sm text-inkSoft">Review reported issues and keep their status up to date.</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <label class="relative block sm:min-w-56">
                    <span class="sr-only">Search maintenance requests</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-inkSoft" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>
                    <input
                        id="maintenance-search"
                        type="search"
                        name="search"
                        placeholder="Search requests"
                        autocomplete="off"
                        class="w-full rounded-xl border border-paper2 bg-white/70 py-2.5 pl-9 pr-3 text-sm text-ink placeholder:text-inkSoft/70 outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15"
                    >
                </label>

                <label>
                    <span class="sr-only">Filter by request status</span>
                    <select
                        id="maintenance-status-filter"
                        name="status"
                        class="w-full rounded-xl border border-paper2 bg-white/70 px-3 py-2.5 text-sm text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15 sm:w-40"
                    >
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ Str::slug($status) }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    <span class="sr-only">Filter by request priority</span>
                    <select
                        id="maintenance-priority-filter"
                        name="priority"
                        class="w-full rounded-xl border border-paper2 bg-white/70 px-3 py-2.5 text-sm text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15 sm:w-36"
                    >
                        <option value="">All priorities</option>
                        @foreach ($priorities as $priority)
                            <option value="{{ Str::lower($priority) }}">{{ $priority }} priority</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>

        <div class="flex flex-col gap-3 p-3 sm:p-5">
            @foreach ($maintenanceRequests as $request)
                <article
                    data-maintenance-request
                    data-status="{{ Str::slug($request['status']) }}"
                    data-priority="{{ Str::lower($request['priority']) }}"
                    data-search="{{ Str::lower(implode(' ', [$request['id'], $request['title'], $request['category'], $request['room'], $request['tenant'], $request['description']])) }}"
                    class="rounded-xl border border-paper2/90 bg-white/45 p-4 transition hover:border-teal/30 hover:bg-white/70 sm:p-5"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="font-mono text-[11px] font-medium tracking-wide text-inkSoft">{{ $request['id'] }}</span>
                                <span class="h-1 w-1 rounded-full bg-inkSoft/40" aria-hidden="true"></span>
                                <span class="text-xs font-medium text-inkSoft">{{ $request['category'] }}</span>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $request['priority_class'] }}">{{ $request['priority'] }} priority</span>
                            </div>

                            <h3 class="font-display text-lg font-bold leading-snug text-ink">{{ $request['title'] }}</h3>
                            <p class="mt-1.5 max-w-3xl text-sm leading-relaxed text-inkSoft">{{ $request['description'] }}</p>

                            <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-inkSoft">
                                <div class="flex items-center gap-1.5">
                                    <dt class="font-semibold text-ink">Room</dt>
                                    <dd>{{ $request['room'] }}</dd>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <dt class="font-semibold text-ink">Reported by</dt>
                                    <dd>{{ $request['tenant'] }}</dd>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <dt class="font-semibold text-ink">Submitted</dt>
                                    <dd><time datetime="{{ $request['reported_at'] }}">{{ $request['reported_at_label'] }}</time></dd>
                                </div>
                            </dl>
                        </div>

                        <div class="flex items-center justify-between gap-3 border-t border-paper2/70 pt-3 lg:min-w-40 lg:flex-col lg:items-end lg:border-0 lg:pt-0">
                            <span class="text-xs font-medium text-inkSoft lg:hidden">Update status</span>
                            <label>
                                <span class="sr-only">Update status for {{ $request['id'] }}</span>
                                <select
                                    name="request_status[{{ $request['id'] }}]"
                                    data-request-status
                                    class="rounded-xl border border-paper2 bg-white px-3 py-2 text-sm font-semibold text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15"
                                >
                                    @foreach ($statuses as $status)
                                        <option value="{{ Str::slug($status) }}" @selected($status === $request['status'])>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>
                </article>
            @endforeach

            <div data-empty-state hidden class="rounded-xl border border-dashed border-paper2 px-5 py-12 text-center">
                <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-paper2/60 text-tealDeep" aria-hidden="true">
                    <x-svg-icon name="maintenance" class="h-5 w-5" />
                </span>
                <h3 class="mt-3 font-display text-lg font-bold text-ink">No matching requests</h3>
                <p class="mt-1 text-sm text-inkSoft">Try adjusting your search or filters.</p>
            </div>
        </div>

        <p class="border-t border-paper2/80 px-4 py-3 text-xs text-inkSoft sm:px-5">
            Demo data only. Status changes are temporary and reset when you refresh.
        </p>
    </section>

    <script>
        const maintenanceCards = [...document.querySelectorAll('[data-maintenance-request]')];
        const searchInput = document.querySelector('#maintenance-search');
        const statusFilter = document.querySelector('#maintenance-status-filter');
        const priorityFilter = document.querySelector('#maintenance-priority-filter');
        const emptyState = document.querySelector('[data-empty-state]');
        const visibleCount = document.querySelector('[data-visible-count]');
        const statusCounts = {
            pending: document.querySelector('[data-summary-count="pending"]'),
            inProgress: document.querySelector('[data-summary-count="in-progress"]'),
            resolved: document.querySelector('[data-summary-count="resolved"]'),
        };
        function filterRequests() {
            const query = searchInput.value.trim().toLowerCase();
            let visibleRequests = 0;

            maintenanceCards.forEach((card) => {
                const matchesSearch = card.dataset.search.includes(query);
                const matchesStatus = !statusFilter.value || card.dataset.status === statusFilter.value;
                const matchesPriority = !priorityFilter.value || card.dataset.priority === priorityFilter.value;
                const isVisible = matchesSearch && matchesStatus && matchesPriority;

                card.hidden = !isVisible;
                visibleRequests += Number(isVisible);
            });

            visibleCount.textContent = visibleRequests;
            emptyState.hidden = visibleRequests > 0;
        }

        function updateSummary() {
            const counts = { pending: 0, 'in-progress': 0, resolved: 0 };

            maintenanceCards.forEach((card) => {
                counts[card.dataset.status] += 1;
            });

            statusCounts.pending.textContent = counts.pending;
            statusCounts.inProgress.textContent = counts['in-progress'];
            statusCounts.resolved.textContent = counts.resolved;
        }

        searchInput.addEventListener('input', filterRequests);
        statusFilter.addEventListener('change', filterRequests);
        priorityFilter.addEventListener('change', filterRequests);

        maintenanceCards.forEach((card) => {
            const statusSelect = card.querySelector('[data-request-status]');

            statusSelect.addEventListener('change', () => {
                card.dataset.status = statusSelect.value;
                updateSummary();
                filterRequests();
            });
        });
    </script>
</x-admin-layout>