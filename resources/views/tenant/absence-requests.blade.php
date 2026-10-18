<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Absence Requests - DORA</x-slot:title>
    <x-header title="Absence requests" description="Let management know when you'll be away, and track the status of your requests."/>

    @php
        $fieldClass = 'w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper';

        $statusClasses = [
            'pending' => 'bg-copper/15 text-copperDeep',
            'approved' => 'bg-success/10 text-success',
            'rejected' => 'bg-danger/10 text-danger',
        ];
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
            {{ session('success') }}
        </div>
    @endif

    <x-tenant-panel title="Submit an absence request" class="mb-6">
        <form action="{{ route('tenant.absence-request.store') }}" method="POST" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="rounded-xl border border-danger/20 bg-danger/5 p-3 text-sm text-danger">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-4">
                    <x-form-label for="start_date">Departure date</x-form-label>
                    <input type="date" name="start_date" id="start_date" min="{{ now()->toDateString() }}" value="{{ old('start_date') }}" class="{{ $fieldClass }}" required>
                </div>

                <div class="space-y-4">
                    <x-form-label for="end_date">Return date</x-form-label>
                    <input type="date" name="end_date" id="end_date" min="{{ now()->toDateString() }}" value="{{ old('end_date') }}" class="{{ $fieldClass }}" required>
                </div>
            </div>

            <x-form-label for="reason">Reason</x-form-label>
            <textarea name="reason" id="reason" class="{{ $fieldClass }} resize-none min-h-[90px]" placeholder="e.g. Going home for the semester break" required>{{ old('reason') }}</textarea>

            <x-form-label for="remarks">Remarks (optional)</x-form-label>
            <textarea name="remarks" id="remarks" class="{{ $fieldClass }} resize-none min-h-[70px]" placeholder="Anything else management should know?">{{ old('remarks') }}</textarea>

            <button type="submit" class="rounded-full bg-copper px-6 py-2 text-sm font-semibold text-white transition hover:bg-copperDeep">Submit request</button>
        </form>
    </x-tenant-panel>

    <x-tenant-panel :title="'My absence requests ('. count($requests).')'">
        <div class="flex flex-col gap-3">
            @forelse ($requests as $absence)
                <div class="rounded-xl border border-paper2 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-[11px] font-medium tracking-wide text-inkSoft">{{ $absence->reference }}</span>
                        <span class="ml-auto rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize {{ $statusClasses[$absence->status] ?? 'bg-paper2 text-inkSoft' }}">{{ $absence->status }}</span>
                    </div>

                    <h3 class="mt-2 font-display text-[17px] font-bold text-ink">
                        {{ $absence->start_date->format('M j, Y') }} &rarr; {{ $absence->end_date->format('M j, Y') }}
                    </h3>
                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-inkSoft">{{ $absence->reason }}</p>

                    @if ($absence->remarks)
                        <p class="mt-2 whitespace-pre-line text-xs text-inkSoft">
                            <span class="font-semibold text-ink">Your remarks:</span> {{ $absence->remarks }}
                        </p>
                    @endif

                    @if ($absence->admin_remarks)
                        <p class="mt-3 rounded-lg bg-paper2/60 px-3 py-2 text-xs text-inkSoft">
                            <span class="font-semibold text-ink">Management note:</span> {{ $absence->admin_remarks }}
                        </p>
                    @endif

                    <p class="mt-3 text-xs text-inkSoft">
                        Submitted {{ $absence->created_at->format('M j, Y · g:i A') }}
                        @if ($absence->reviewed_at)
                            · Reviewed {{ $absence->reviewed_at->format('M j, Y') }}
                        @endif
                    </p>
                </div>
            @empty
                <div class="flex min-h-[120px] items-center justify-center text-sm text-inkSoft">
                    You haven't filed any absence request yet.
                </div>
            @endforelse
        </div>
    </x-tenant-panel>
</x-tenant-layout>