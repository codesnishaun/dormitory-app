<x-admin-layout>
    <x-slot:title>Submeters</x-slot:title>

    <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
            <div>
                <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-teal/15 bg-teal/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal"></span>
                    Meter operations
                </div>
                <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Submeters</h1>
                <p class="mt-2 max-w-2xl text-sm text-inkSoft sm:text-base">
                    Monitor physical meter records, current rooms, installation history, and lifecycle status.
                </p>
            </div>

            <button type="button" data-open-dialog="add-submeter-modal" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-teal px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path d="M8 2.5v11M2.5 8h11" stroke-linecap="round" />
                </svg>
                Add submeter
            </button>
        </header>

        @if (session('success'))
            <div class="rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="rounded-xl border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4" aria-label="Submeter summary">
            <div class="rounded-2xl border border-ink/10 bg-paper3 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-inkSoft">Total</p>
                <div class="mt-3 flex items-baseline justify-between gap-3">
                    <span class="text-2xl font-bold text-ink">{{ $summary['total'] }}</span>
                    <span class="rounded-full bg-ink/5 px-2 py-1 text-[10px] font-semibold uppercase text-inkSoft">Meters</span>
                </div>
            </div>

            <div class="rounded-2xl border border-success/15 bg-success/10 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-success">Active</p>
                <div class="mt-3 flex items-baseline justify-between gap-3">
                    <span class="text-2xl font-bold text-success">{{ $summary['active'] }}</span>
                    <span class="rounded-full bg-success/10 px-2 py-1 text-[10px] font-semibold uppercase text-success">Live</span>
                </div>
            </div>

            <div class="rounded-2xl border border-copper/20 bg-copper/10 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-copper">Inactive</p>
                <div class="mt-3 flex items-baseline justify-between gap-3">
                    <span class="text-2xl font-bold text-copper">{{ $summary['inactive'] }}</span>
                    <span class="rounded-full bg-copper/10 px-2 py-1 text-[10px] font-semibold uppercase text-copper">Idle</span>
                </div>
            </div>

            <div class="rounded-2xl border border-ink/10 bg-paper2 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-inkSoft">Retired</p>
                <div class="mt-3 flex items-baseline justify-between gap-3">
                    <span class="text-2xl font-bold text-ink">{{ $summary['retired'] }}</span>
                    <span class="rounded-full bg-ink/5 px-2 py-1 text-[10px] font-semibold uppercase text-inkSoft">Closed</span>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-ink/10 bg-paper3 p-4 sm:p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-display text-xl font-bold text-ink">Meter inventory</h2>
                    <p class="mt-1 text-xs text-inkSoft">{{ $submeters->total() }} result{{ $submeters->total() === 1 ? '' : 's' }}</p>
                </div>

                <form method="GET" action="{{ route('admin.submeter.index') }}" class="flex w-full flex-col gap-2 md:w-auto md:flex-row md:items-center">
                    <label class="relative block w-full md:w-72">
                        <span class="sr-only">Search submeters</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-inkSoft/70" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <circle cx="8.75" cy="8.75" r="5.75" />
                            <path d="m13 13 4 4" stroke-linecap="round" />
                        </svg>
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search meter, notes, or room" class="h-11 w-full rounded-xl border border-ink/10 bg-paper pl-9 pr-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
                    </label>

                    <select name="status" class="h-11 rounded-xl border border-ink/10 bg-paper px-3 text-sm text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All statuses</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="retired" {{ $status === 'retired' ? 'selected' : '' }}>Retired</option>
                    </select>

                    <button type="submit" class="h-11 rounded-xl bg-ink px-4 text-sm font-semibold text-paper transition hover:bg-ink/90">Apply</button>
                </form>
            </div>

            @if ($submeters->isEmpty())
                <div class="mt-6 rounded-xl border border-dashed border-ink/15 bg-paper px-6 py-10 text-center text-sm text-inkSoft">
                    No submeters match the current filters.
                </div>
            @else
                <div class="mt-6 overflow-hidden rounded-2xl border border-ink/10">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-ink/10 bg-paper text-left">
                            <thead class="bg-paper2 text-[11px] font-semibold uppercase tracking-[0.12em] text-inkSoft">
                                <tr>
                                    <th class="px-4 py-3">Meter</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Current room</th>
                                    <th class="px-4 py-3">Installed</th>
                                    <th class="px-4 py-3">Notes</th>
                                    <th class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink/10">
                                @foreach ($submeters as $submeter)
                                    @php
                                        $assignment = $submeter->currentAssignment;
                                        $room = $assignment?->room;
                                        $installationDate = $assignment?->installed_at?->format('M j, Y') ?? 'Not assigned';
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-4">
                                            <div class="font-mono text-sm font-semibold text-ink">{{ $submeter->meter_number }}</div>
                                            @if ($submeter->status === 'retired')
                                                <div class="mt-1 text-[11px] uppercase tracking-[0.12em] text-inkSoft">Retired</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            @php
                                                $statusClass = match ($submeter->status) {
                                                    'active' => 'bg-success/10 text-success',
                                                    'inactive' => 'bg-copper/10 text-copper',
                                                    'retired' => 'bg-ink/5 text-inkSoft',
                                                    default => 'bg-paper2 text-inkSoft',
                                                };
                                            @endphp
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $statusClass }}">
                                                {{ $submeter->status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if ($room)
                                                <div class="text-sm font-semibold text-ink">{{ $room->room_number }}</div>
                                                <div class="mt-1 text-xs text-inkSoft">{{ $assignment->installed_by ? 'Installed by '.$assignment->installedBy?->name : 'Assigned' }}</div>
                                            @else
                                                <span class="text-sm text-inkSoft">Not assigned</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm text-ink">{{ $installationDate }}</div>
                                            @if ($assignment)
                                                <div class="mt-1 text-xs text-inkSoft">{{ $assignment->uninstalled_at ? 'Removed '.$assignment->uninstalled_at->format('M j, Y') : 'Currently active' }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="max-w-xs text-sm leading-6 text-inkSoft">
                                                {{ $submeter->notes ?: 'No notes recorded.' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-2">
                                                @if ($submeter->status !== 'retired')
                                                    <button type="button" data-submeter-id="{{ $submeter->id }}" data-meter-number="{{ $submeter->meter_number }}" data-status="{{ $submeter->status }}" data-notes="{{ $submeter->notes ?? '' }}" class="edit-submeter-btn rounded-lg border border-ink/10 bg-paper2 px-3 py-2 text-left text-xs font-semibold text-ink transition hover:bg-paper">Edit</button>
                                                @endif
                                                @if ($submeter->status !== 'retired')
                                                    <form method="POST" action="{{ route('admin.submeter.retire', $submeter) }}" onsubmit="return confirm('Retire {{ $submeter->meter_number }}? This keeps its history but prevents future use.');">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full rounded-lg border border-ink/10 bg-paper2 px-3 py-2 text-left text-xs font-semibold text-ink transition hover:bg-paper">Retire</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($submeters->hasPages())
                <div class="mt-6">
                    {{ $submeters->links() }}
                </div>
            @endif
        </section>
    </div>

    <dialog id="add-submeter-modal" aria-labelledby="add-submeter-title" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-3xl border border-paper/15 bg-paper3 p-0 text-ink shadow-2xl backdrop:bg-ink/65 backdrop:backdrop-blur-sm">
        <form method="POST" action="{{ route('admin.submeter.store') }}" class="space-y-5 p-6">
            @csrf
            <div class="flex items-start justify-between gap-4 border-b border-paper2 pb-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-teal">New meter</p>
                    <h2 id="add-submeter-title" class="mt-1 font-display text-2xl font-bold text-ink">Add submeter</h2>
                </div>
                <button type="button" data-close-dialog aria-label="Close add meter form" class="rounded-xl p-2 text-inkSoft transition hover:bg-paper2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">&times;</button>
            </div>

            <div>
                <label for="meter_number" class="mb-2 block text-sm font-semibold text-ink">Meter number <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="meter_number" name="meter_number" value="{{ old('meter_number') }}" required aria-describedby="meter-number-help @error('meter_number') meter-number-error @enderror" @class(['h-11 w-full rounded-xl border bg-paper px-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15', 'border-danger focus:border-danger focus:ring-danger/15' => $errors->has('meter_number'), 'border-ink/10' => ! $errors->has('meter_number')])>
                <p id="meter-number-help" class="mt-1.5 text-xs text-inkSoft">Use the unique identifier printed on the meter.</p>
                @error('meter_number')
                    <p id="meter-number-error" class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="mb-2 block text-sm font-semibold text-ink">Status</label>
                <select id="status" name="status" class="h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15">
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="notes" class="mb-2 block text-sm font-semibold text-ink">Notes</label>
                <textarea id="notes" name="notes" rows="4" class="w-full rounded-xl border border-ink/10 bg-paper px-3 py-2 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">{{ old('notes') }}</textarea>
                @error('notes')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" data-close-dialog class="rounded-xl border border-ink/10 bg-paper2 px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-paper">Cancel</button>
                <button type="submit" class="rounded-xl bg-teal px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Create submeter</button>
            </div>
        </form>
    </dialog>

    <dialog id="edit-submeter-modal" aria-labelledby="edit-submeter-title" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-3xl border border-paper/15 bg-paper3 p-0 text-ink shadow-2xl backdrop:bg-ink/65 backdrop:backdrop-blur-sm">
        <form method="POST" id="edit-submeter-form" class="space-y-5 p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="submeter_id" id="edit-submeter-id" value="{{ old('submeter_id') }}">
            <div class="flex items-start justify-between gap-4 border-b border-paper2 pb-4">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-teal">Update meter</p>
                    <h2 id="edit-submeter-title" class="mt-1 font-display text-2xl font-bold text-ink">Edit submeter</h2>
                </div>
                <button type="button" data-close-dialog aria-label="Close edit meter form" class="rounded-xl p-2 text-inkSoft transition hover:bg-paper2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">&times;</button>
            </div>

            <div>
                <label for="edit-meter-number" class="mb-2 block text-sm font-semibold text-ink">Meter number</label>
                <input id="edit-meter-number" name="meter_number" required class="h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
                @error('meter_number', 'editSubmeter')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="edit-status" class="mb-2 block text-sm font-semibold text-ink">Status</label>
                <select id="edit-status" name="status" class="h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                @error('status', 'editSubmeter')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="edit-notes" class="mb-2 block text-sm font-semibold text-ink">Notes</label>
                <textarea id="edit-notes" name="notes" rows="4" class="w-full rounded-xl border border-ink/10 bg-paper px-3 py-2 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15"></textarea>
                @error('notes', 'editSubmeter')
                    <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" data-close-dialog class="rounded-xl border border-ink/10 bg-paper2 px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-paper">Cancel</button>
                <button type="submit" class="rounded-xl bg-teal px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-tealDeep">Save changes</button>
            </div>
        </form>
    </dialog>

    <script>
        document.addEventListener('click', function (event) {
            if (!(event.target instanceof Element)) {
                return;
            }

            const openTrigger = event.target.closest('[data-open-dialog]');

            if (openTrigger) {
                document.getElementById(openTrigger.dataset.openDialog)?.showModal();
            }

            const closeTrigger = event.target.closest('[data-close-dialog]');

            if (closeTrigger) {
                closeTrigger.closest('dialog')?.close();
            }

            const trigger = event.target.closest('.edit-submeter-btn');

            if (!trigger) {
                return;
            }

            const id = trigger.dataset.submeterId;
            const meterNumber = trigger.dataset.meterNumber;
            const status = trigger.dataset.status;
            const notes = trigger.dataset.notes || '';
            const form = document.getElementById('edit-submeter-form');
            const modal = document.getElementById('edit-submeter-modal');

            form.action = @js(route('admin.submeter.update', ['submeter' => '__SUBMETER__'])).replace('__SUBMETER__', id);
            document.getElementById('edit-submeter-id').value = id;
            document.getElementById('edit-meter-number').value = meterNumber;
            document.getElementById('edit-status').value = status;
            document.getElementById('edit-notes').value = notes;
            modal.showModal();
        });

        @if ($errors->any() && ! old('submeter_id'))
            document.getElementById('add-submeter-modal').showModal();
            document.getElementById('meter_number').focus();
        @endif

        @if ($errors->any() && old('submeter_id'))
            const previousSubmeterId = @js(old('submeter_id'));
            const editForm = document.getElementById('edit-submeter-form');
            if (previousSubmeterId) {
                editForm.action = @js(route('admin.submeter.update', ['submeter' => '__SUBMETER__'])).replace('__SUBMETER__', previousSubmeterId);
                document.getElementById('edit-meter-number').value = @js(old('meter_number'));
                document.getElementById('edit-status').value = @js(old('status'));
                document.getElementById('edit-notes').value = @js(old('notes'));
            }
            document.getElementById('edit-submeter-modal').showModal();
        @endif

        document.querySelectorAll('dialog').forEach(function (dialog) {
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) {
                    dialog.close();
                }
            });
        });
    </script>
</x-admin-layout>
