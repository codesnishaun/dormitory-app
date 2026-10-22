<x-admin-layout>
    <x-slot:title>Tenants - Admin - DORA</x-slot:title>

    <x-header
        title="Tenants"
        description="Manage resident profiles and room assignments."
    />
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
    <section aria-labelledby="applications-heading" class="mb-6 rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="applications-heading"
                class="font-display text-lg font-bold">
                Applications awaiting review
            </h2>

            <span class="rounded-full bg-paper2 px-3 py-1 text-xs font-semibold text-inkSoft">
                {{ $pendingApplications->count() }} pending
            </span>
        </div>

        @if ($pendingApplications->isEmpty())
            <div class="flex min-h-28 items-center justify-center py-5 text-center">
                <p class="text-sm text-inkSoft">
                    No pending applications right now.
                </p>
            </div>
        @else
            <ul class="mt-4 flex flex-col gap-3">
                @foreach ($pendingApplications as $application)
                    <li class="rounded-xl border border-paper2/80 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">
                                    {{ $application->applicant_name }}
                                </h3>

                                <p class="mt-1 text-sm text-inkSoft">
                                    {{ $application->email }}
                                </p>

                                <p class="mt-1 text-sm text-inkSoft">
                                    Contact: {{ $application->phone }}
                                </p>

                                <p class="mt-1 text-sm text-inkSoft">
                                    Preferred room:
                                    {{ $application->preferredRoom?->room_number ?? 'No preference' }}
                                </p>

                                <p class="mt-1 text-sm text-inkSoft">
                                    Move-in date:
                                    {{ $application->move_in_date }}
                                </p>
                            </div>

                            <span class="rounded-full bg-copper/10 px-2.5 py-1 text-xs font-semibold text-copperDeep">
                                Pending review
                            </span>
                        </div>

                        @if ($application->message)
                            <p class="mt-3 text-sm text-inkSoft">
                                {{ $application->message }}
                            </p>
                        @endif
                        
                            <div class="mt-4 flex flex-wrap gap-2">
                            <form action="{{ route('applications.approve', $application) }}" method="POST">
                                @csrf
                                <x-button
                                    type="submit"
                                    variant="teal"
                                >
                                    Approve
                                </x-button>
                            </form>

                            <form action="{{ route('applications.reject', $application) }}"  method="POST">
                                @csrf
                                <x-button
                                    type="submit"
                                    variant="danger"
                                >
                                    Reject
                                </x-button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="reviewed-applications-heading"
        class="mb-6 rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="reviewed-applications-heading"
                class="font-display text-lg font-bold">
                Recently Reviewed Applications
            </h2>

            <span class="rounded-full bg-paper2 px-3 py-1 text-xs font-semibold text-inkSoft">
                {{ $reviewedApplications->count() }} reviewed
            </span>
        </div>

        @if ($reviewedApplications->isEmpty())
            <div class="flex min-h-28 items-center justify-center py-5 text-center">
                <p class="text-sm text-inkSoft">
                    No reviewed applications yet.
                </p>
            </div>
        @else
            <ul class="mt-4 flex flex-col gap-3">
                @foreach ($reviewedApplications as $application)
                    <li class="rounded-xl border border-paper2/80 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">
                                    {{ $application->applicant_name }}
                                </h3>

                                <p class="mt-1 text-sm text-inkSoft">
                                    {{ $application->email }}
                                </p>

                                <p class="mt-1 text-sm text-inkSoft">
                                    Preferred room:
                                    {{ $application->preferredRoom?->room_number ?? 'No preference' }}
                                </p>

                                <p class="mt-1 text-sm text-inkSoft">
                                    Reviewed:
                                    {{ $application->reviewed_at ?? 'Date unavailable' }}
                                </p>

                                @if ($application->admin_remarks)
                                    <p class="mt-2 text-sm text-inkSoft">
                                        Remarks: {{ $application->admin_remarks }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $application->status === 'approved'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800' }}">
                                    {{ ucfirst($application->status) }}
                                </span>

                                @if ($application->status === 'approved' && ($application->user?->tenantAccountInvitations->isNotEmpty() ?? false))
                                    <form
                                        action="{{ route('applications.destroy', $application) }}"
                                        method="POST"
                                        onsubmit="return confirm('Remove this reviewed application? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')

                                        <x-button type="submit" variant="danger" size="sm">
                                            Remove
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                         @if ($application->status === 'approved')
                            <form action="{{ route('applications.assign-room', $application) }}"
                                  method="POST"
                                  class="mt-4 flex flex-wrap items-end gap-2">
                                @csrf

                                <div>
                                    <x-form-input
                                        name="room_id"
                                        label="Available Rooms"
                                        type="select"
                                        :value="$application->preferred_room_id"
                                        :options="$rooms->mapWithKeys(fn ($room) => [
                                            $room->id => 'Room ' . $room->room_number
                                        ])->all()"
                                        required
                                    />
                                </div>
                                
                                <x-button type="submit" variant="teal" class="!px-3.5 !py-2.5 text-[14.5px]!" >
                                    {{ $application->user_id ? 'Resend invitation' : 'Confirm room & send invitation' }}
                                </x-button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="tenant-list-heading" class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="tenant-list-heading" class="font-display text-lg font-bold">
                    Tenant accounts <span class="text-inkSoft">({{ $tenants->total() }})</span>
                </h2>
                <p class="mt-1 text-sm text-inkSoft">Edit tenant details or archive an account while preserving its history.</p>
            </div>
            <button type="button" data-tenant-create
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                <span aria-hidden="true" class="text-lg leading-none">+</span>
                Add tenant
            </button>
        </div>

        @if ($tenants->isEmpty())
            <div class="rounded-xl border border-dashed border-ink/15 bg-paper px-5 py-10 text-center">
                <h3 class="font-semibold text-ink">No tenant accounts yet</h3>
                <p class="mt-1 text-sm text-inkSoft">Approved applicants appear here after their room is confirmed.</p>
            </div>
        @else
            <div class="-mx-1 hidden overflow-x-auto px-1 md:block">
                <table class="w-full min-w-[760px] border-collapse text-left">
                    <thead>
                        <tr class="border-b border-paper2 text-[11px] font-semibold uppercase tracking-wider text-inkSoft">
                            <th scope="col" class="px-3 py-3">Tenant</th>
                            <th scope="col" class="px-3 py-3">Room</th>
                            <th scope="col" class="px-3 py-3">Contact</th>
                            <th scope="col" class="px-3 py-3">Move-in</th>
                            <th scope="col" class="px-3 py-3">Assignment</th>
                            <th scope="col" class="px-3 py-3"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-paper2/70 text-sm">
                        @foreach ($tenants as $tenant)
                            @php($assignment = $tenant->roomAssignments->first())
                            <tr>
                                <th scope="row" class="px-3 py-3.5 font-normal">
                                    <span class="block font-semibold text-ink">{{ $tenant->name }}</span>
                                    <span class="mt-0.5 block text-xs text-inkSoft">{{ $tenant->email }}</span>
                                </th>
                                <td class="whitespace-nowrap px-3 py-3.5">{{ $assignment?->room?->room_number ?? 'Unassigned' }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5">{{ $assignment?->application?->phone ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-3.5">{{ $assignment?->start_date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-3 py-3.5">
                                    @if ($assignment)
                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $assignment->status === 'occupied' ? 'bg-success/15 text-success' : 'bg-copper/10 text-copperDeep' }}">
                                            {{ ucfirst($assignment->status) }}
                                        </span>
                                    @else
                                        <span class="rounded-full bg-paper2 px-2.5 py-1 text-[11px] font-semibold text-inkSoft">No room assigned</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" data-tenant-edit data-tenant-id="{{ $tenant->id }}" data-tenant-name="{{ $tenant->name }}" data-tenant-email="{{ $tenant->email }}"
                                            class="rounded-full border border-paper2 px-3.5 py-1.5 text-xs font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                            Edit
                                        </button>
                                        <button type="button" data-tenant-archive data-tenant-id="{{ $tenant->id }}" data-tenant-name="{{ $tenant->name }}"
                                            class="rounded-full border border-danger/40 px-3.5 py-1.5 text-xs font-semibold text-danger transition hover:bg-danger/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                                            Archive
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="flex flex-col gap-3 md:hidden">
                @foreach ($tenants as $tenant)
                    @php($assignment = $tenant->roomAssignments->first())
                    <li class="rounded-xl border border-paper2/80 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold">{{ $tenant->name }}</h3>
                                <p class="mt-0.5 break-all text-xs text-inkSoft">{{ $tenant->email }}</p>
                            </div>
                            @if ($assignment)
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $assignment->status === 'occupied' ? 'bg-success/15 text-success' : 'bg-copper/10 text-copperDeep' }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                            @else
                                <span class="shrink-0 rounded-full bg-paper2 px-2.5 py-1 text-[11px] font-semibold text-inkSoft">Unassigned</span>
                            @endif
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Room</dt>
                                <dd class="mt-0.5">{{ $assignment?->room?->room_number ?? 'Unassigned' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Contact</dt>
                                <dd class="mt-0.5">{{ $assignment?->application?->phone ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-semibold uppercase tracking-wider text-inkSoft">Move-in</dt>
                                <dd class="mt-0.5">{{ $assignment?->start_date?->format('M j, Y') ?? '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex gap-2 border-t border-paper2/70 pt-3">
                            <button type="button" data-tenant-edit data-tenant-id="{{ $tenant->id }}" data-tenant-name="{{ $tenant->name }}" data-tenant-email="{{ $tenant->email }}"
                                class="flex-1 rounded-full border border-paper2 px-3 py-2 text-xs font-semibold transition hover:bg-paper2/60 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                                Edit
                            </button>
                            <button type="button" data-tenant-archive data-tenant-id="{{ $tenant->id }}" data-tenant-name="{{ $tenant->name }}"
                                class="flex-1 rounded-full border border-danger/40 px-3 py-2 text-xs font-semibold text-danger transition hover:bg-danger/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
                                Archive
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5">
                {{ $tenants->links() }}
            </div>
        @endif
    </section>

    @if ($archivedTenants->isNotEmpty())
        <section aria-labelledby="archived-tenants-heading" class="rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
            <div class="mb-4">
                <h2 id="archived-tenants-heading" class="font-display text-lg font-bold">
                    Archived accounts <span class="text-inkSoft">({{ $archivedTenants->total() }})</span>
                </h2>
                <p class="mt-1 text-sm text-inkSoft">Archived accounts cannot sign in. Restore an account to make it active again.</p>
            </div>

            <ul class="flex flex-col gap-3">
                @foreach ($archivedTenants as $archivedTenant)
                    <li class="flex flex-col gap-3 rounded-xl border border-paper2/80 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="break-words font-semibold text-ink">{{ $archivedTenant->name }}</p>
                            <p class="mt-0.5 break-all text-xs text-inkSoft">{{ $archivedTenant->email }}</p>
                            <p class="mt-1 text-xs text-inkSoft">
                                Archived {{ $archivedTenant->deleted_at?->format('M j, Y') }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.tenant.restore', $archivedTenant->id) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                class="w-full rounded-full border border-teal/30 px-4 py-2 text-xs font-semibold text-teal transition hover:bg-teal/8 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal sm:w-auto">
                                Restore account
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5">
                {{ $archivedTenants->links() }}
            </div>
        </section>
    @endif

    @include('admin.tenant.create')
    @include('admin.tenant.edit')
    @include('admin.tenant.archive')
</x-admin-layout>
