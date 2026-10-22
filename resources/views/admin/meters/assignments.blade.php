<x-admin-layout>
    <x-slot:title>Meter assignments</x-slot:title>

    <div class="mx-auto w-full space-y-6">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal">Submeter management</p>
                <h1 class="mt-2 font-display text-3xl font-bold text-ink">Meter assignments</h1>
                <p class="mt-2 text-sm text-inkSoft">Install, replace, and record readings for room meters.</p>
            </div>
            <a href="{{ route('admin.submeter.index') }}" class="text-sm font-semibold text-teal hover:text-tealDeep">Manage submeters</a>
        </header>

        @if (session('success'))
            <div class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-2xl border border-ink/8 bg-paper3 p-5 shadow-sm sm:p-6" aria-labelledby="assign-meter-heading">
            <h2 id="assign-meter-heading" class="font-display text-xl font-bold text-ink">Assign a meter</h2>
            <p class="mt-1 text-sm text-inkSoft">The initial reading is saved with the installation date.</p>

            <form method="POST" action="{{ route('admin.meter-assignments.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @csrf
                <label class="block text-sm font-medium text-ink">
                    Room
                    <select name="room_id" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                        <option value="">Select a room</option>
                        @foreach ($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id') == $room->id)>{{ $room->room_number }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-ink">
                    Active submeter
                    <select name="submeter_id" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                        <option value="">Select a submeter</option>
                        @foreach ($submeters as $submeter)
                            <option value="{{ $submeter->id }}" @selected(old('submeter_id') == $submeter->id)>{{ $submeter->meter_number }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm font-medium text-ink">
                    Installation date
                    <input type="date" name="installed_at" value="{{ old('installed_at', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                </label>
                <label class="block text-sm font-medium text-ink">
                    Initial reading (kWh)
                    <input type="number" name="initial_reading" value="{{ old('initial_reading') }}" min="0" max="999999999.999" step="0.001" required class="mt-1.5 h-11 w-full rounded-xl border border-ink/10 bg-white px-3 text-sm">
                </label>
                <div class="sm:col-span-2 xl:col-span-4">
                    <button type="submit" @disabled($rooms->isEmpty() || $submeters->isEmpty()) class="inline-flex h-11 items-center justify-center rounded-xl bg-teal px-5 text-sm font-semibold text-white transition hover:bg-tealDeep disabled:cursor-not-allowed disabled:opacity-50">Assign meter</button>
                    @if ($submeters->isEmpty())
                        <span class="ml-2 text-xs text-inkSoft">No unassigned active submeters are available.</span>
                    @endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-sm" aria-labelledby="assignment-history-heading">
            <div class="border-b border-ink/8 px-5 py-4 sm:px-6">
                <h2 id="assignment-history-heading" class="font-display text-xl font-bold text-ink">Assignment and reading history</h2>
                <p class="mt-1 text-xs text-inkSoft">Closed assignments remain available for historical billing.</p>
            </div>

            <div class="divide-y divide-ink/8">
                @forelse ($assignments as $assignment)
                    <article class="space-y-4 px-5 py-5 sm:px-6">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="font-semibold text-ink">{{ $assignment->room->room_number }} <span class="font-normal text-inkSoft">· {{ $assignment->submeter->meter_number }}</span></h3>
                                <p class="mt-1 text-xs text-inkSoft">
                                    Installed {{ $assignment->installed_at->format('M j, Y') }}
                                    @if ($assignment->uninstalled_at)
                                        · Removed {{ $assignment->uninstalled_at->format('M j, Y') }}
                                    @else
                                        · <span class="font-semibold text-success">Active</span>
                                    @endif
                                </p>
                            </div>
                            @if (! $assignment->uninstalled_at)
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.meter-readings.store', $assignment) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <label class="sr-only" for="reading-date-{{ $assignment->id }}">Reading date</label>
                                        <input id="reading-date-{{ $assignment->id }}" type="date" name="reading_date" max="{{ today()->toDateString() }}" value="{{ today()->toDateString() }}" required class="h-10 rounded-lg border border-ink/10 bg-white px-2 text-xs">
                                        <label class="sr-only" for="reading-value-{{ $assignment->id }}">Reading in kWh</label>
                                        <input id="reading-value-{{ $assignment->id }}" type="number" name="reading_value" min="0" max="999999999.999" step="0.001" placeholder="kWh reading" required class="h-10 w-32 rounded-lg border border-ink/10 bg-white px-2 text-xs">
                                        <button type="submit" class="h-10 rounded-lg border border-teal/20 px-3 text-xs font-semibold text-teal hover:bg-teal/5">Record reading</button>
                                    </form>
                                    <details class="rounded-lg border border-ink/10 px-3 py-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-inkSoft">Replace / remove</summary>
                                        <form method="POST" action="{{ route('admin.meter-assignments.close', $assignment) }}" class="mt-3 flex flex-wrap items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label class="sr-only" for="close-date-{{ $assignment->id }}">Removal date</label>
                                            <input id="close-date-{{ $assignment->id }}" type="date" name="uninstalled_at" min="{{ $assignment->installed_at->addDay()->toDateString() }}" max="{{ today()->toDateString() }}" required class="h-10 rounded-lg border border-ink/10 bg-white px-2 text-xs">
                                            <label class="sr-only" for="final-reading-{{ $assignment->id }}">Final reading in kWh</label>
                                            <input id="final-reading-{{ $assignment->id }}" type="number" name="final_reading" min="0" max="999999999.999" step="0.001" placeholder="Final kWh" required class="h-10 w-28 rounded-lg border border-ink/10 bg-white px-2 text-xs">
                                            <button type="submit" class="h-10 rounded-lg bg-ink px-3 text-xs font-semibold text-white">Save final reading</button>
                                        </form>
                                    </details>
                                </div>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[32rem] text-left text-xs">
                                <thead class="text-[10px] uppercase tracking-wider text-inkSoft">
                                    <tr>
                                        <th class="py-2 pr-3">Reading date</th>
                                        <th class="py-2 pr-3">Reading (kWh)</th>
                                        <th class="py-2">Recorded by</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-ink/5">
                                    @forelse ($assignment->meterReadings as $reading)
                                        <tr>
                                            <td class="py-2 pr-3 font-mono">{{ $reading->reading_date->format('Y-m-d') }}</td>
                                            <td class="py-2 pr-3 font-mono">{{ number_format((float) $reading->reading_value, 3) }}</td>
                                            <td class="py-2">{{ $reading->recorder?->name ?? 'Unknown' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="py-3 text-inkSoft">No readings recorded.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </article>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-inkSoft">No meter assignments have been recorded.</p>
                @endforelse
            </div>

            @if ($assignments->hasPages())
                <div class="border-t border-ink/8 px-5 py-4">{{ $assignments->links() }}</div>
            @endif
        </section>
    </div>
</x-admin-layout>
