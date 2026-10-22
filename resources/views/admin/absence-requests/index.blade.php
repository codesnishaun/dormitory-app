<x-admin-layout>
    <x-slot:title>
        Absence Requests
    </x-slot:title>

    <x-header
        title="Absence requests"
        description="Review tenant absence requests and approve or reject them."
    />

    @php
        $statusClasses = [
            'pending' => 'bg-copper/15 text-copperDeep',
            'approved' => 'bg-success/10 text-success',
            'rejected' => 'bg-danger/10 text-danger',
        ];
        $fieldClass = 'w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-teal focus:ring-2 focus:ring-teal/15';
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm font-semibold text-danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-danger/20 bg-danger/5 p-3 text-sm text-danger">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section aria-label="Absence request summary" class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'All requests', 'value' => $summary['total'], 'detail' => 'Filed by tenants', 'tone' => 'text-teal'],
            ['label' => 'Pending', 'value' => $summary['pending'], 'detail' => 'Waiting for review', 'tone' => 'text-copperDeep'],
            ['label' => 'Approved', 'value' => $summary['approved'], 'detail' => 'Accepted requests', 'tone' => 'text-success'],
            ['label' => 'Rejected', 'value' => $summary['rejected'], 'detail' => 'Declined requests', 'tone' => 'text-danger'],
        ] as $stat)
            <article class="rounded-2xl border border-paper2 bg-paper3 p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-inkSoft">{{ $stat['label'] }}</p>
                        <p class="mt-2 font-display text-3xl font-semibold leading-none text-ink">{{ $stat['value'] }}</p>
                    </div>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-paper2/70 {{ $stat['tone'] }}" aria-hidden="true">
                        <x-svg-icon name="tenants" class="h-4.5 w-4.5" />
                    </span>
                </div>
                <p class="mt-3 text-xs text-inkSoft">{{ $stat['detail'] }}</p>
            </article>
        @endforeach
    </section>

    <section aria-labelledby="absence-heading" class="overflow-hidden rounded-2xl border border-paper2 bg-paper3 shadow-sm shadow-ink/5">
        <div class="flex flex-col gap-4 border-b border-paper2/80 p-4 sm:p-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 id="absence-heading" class="font-display text-xl font-bold text-ink">Absence requests</h2>
                <p class="mt-1 text-sm text-inkSoft">Showing {{ count($absenceRequests) }} {{ $activeStatus ?: 'total' }} request(s).</p>
            </div>

            <form method="GET" action="{{ route('admin.absence-request.index') }}">
                <label>
                    <span class="sr-only">Filter by status</span>
                    <select name="status" onchange="this.form.submit()" class="rounded-xl border border-paper2 bg-white/70 px-3 py-2.5 text-sm capitalize text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15 sm:w-44">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected($activeStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div class="flex flex-col gap-3 p-3 sm:p-5">
            @forelse ($absenceRequests as $absence)
                <article class="rounded-xl border border-paper2/90 bg-white/45 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-[11px] font-medium tracking-wide text-inkSoft">{{ $absence->reference }}</span>
                        <span class="ml-auto rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize {{ $statusClasses[$absence->status] ?? 'bg-paper2 text-inkSoft' }}">{{ $absence->status }}</span>
                    </div>

                    <h3 class="mt-2 font-display text-lg font-bold leading-snug text-ink">
                        {{ $absence->start_date->format('M j, Y') }} &rarr; {{ $absence->end_date->format('M j, Y') }}
                    </h3>
                    <p class="mt-1.5 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-inkSoft">{{ $absence->reason }}</p>

                    @if ($absence->remarks)
                        <p class="mt-2 whitespace-pre-line text-xs text-inkSoft"><span class="font-semibold text-ink">Tenant remarks:</span> {{ $absence->remarks }}</p>
                    @endif

                    <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-inkSoft">
                        <div class="flex items-center gap-1.5">
                            <dt class="font-semibold text-ink">Tenant</dt>
                            <dd>{{ $absence->roomAssignment?->user?->name ?? 'Unknown' }}</dd>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <dt class="font-semibold text-ink">Room</dt>
                            <dd>{{ $absence->roomAssignment?->room?->room_number ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <dt class="font-semibold text-ink">Submitted</dt>
                            <dd><time datetime="{{ $absence->created_at->toDateString() }}">{{ $absence->created_at->format('M j, Y') }}</time></dd>
                        </div>
                    </dl>

                    @if ($absence->isPending())
                        <div class="mt-4 grid gap-3 border-t border-paper2/70 pt-4 lg:grid-cols-[1fr_auto] lg:items-end">
                            <div>
                                <label for="admin_remarks_{{ $absence->id }}" class="mb-1 block text-xs font-semibold text-ink">Admin remarks <span class="font-normal text-inkSoft">(required when rejecting)</span></label>
                                <textarea id="admin_remarks_{{ $absence->id }}" name="admin_remarks" form="approve-{{ $absence->id }}" rows="2" class="{{ $fieldClass }} resize-none"></textarea>
                            </div>

                            <div class="flex gap-2">
                                <form id="approve-{{ $absence->id }}" action="{{ route('admin.absence-request.approve', $absence) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <x-button type="submit" variant="teal" size="sm">Approve</x-button>
                                </form>

                                <form action="{{ route('admin.absence-request.reject', $absence) }}" method="POST"
                                      onsubmit="this.querySelector('[name=admin_remarks]').value = document.getElementById('admin_remarks_{{ $absence->id }}').value;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="admin_remarks" value="">
                                    <x-button type="submit" variant="danger" size="sm">Reject</x-button>
                                </form>
                            </div>
                        </div>
                    @else
                        <p class="mt-4 border-t border-paper2/70 pt-3 text-xs text-inkSoft">
                            {{ ucfirst($absence->status) }}
                            @if ($absence->reviewer) by {{ $absence->reviewer->name }} @endif
                            @if ($absence->reviewed_at) on {{ $absence->reviewed_at->format('M j, Y') }} @endif
                            @if ($absence->admin_remarks) &mdash; {{ $absence->admin_remarks }} @endif
                        </p>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-paper2 px-5 py-12 text-center">
                    <h3 class="font-display text-lg font-bold text-ink">No absence requests</h3>
                    <p class="mt-1 text-sm text-inkSoft">Nothing to review right now.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-admin-layout>