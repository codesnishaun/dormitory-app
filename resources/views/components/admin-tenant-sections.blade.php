@props([
    'pendingApplications' => null,
    'reviewedApplications' => null,
    'rooms' => null,
])

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