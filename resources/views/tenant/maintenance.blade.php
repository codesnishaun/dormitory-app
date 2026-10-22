<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Maintenance - DORA</x-slot:title>
    <x-header title="Maintenance" description="Report an issue or check your request status."/>

    @php
        $fieldClass = 'w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper';

        $priorityClasses = [
            'High' => 'bg-danger/10 text-danger',
            'Medium' => 'bg-copper/15 text-copperDeep',
            'Low' => 'bg-paper2 text-inkSoft',
        ];

        $statusClasses = [
            'Pending' => 'bg-copper/15 text-copperDeep',
            'In Progress' => 'bg-teal/10 text-tealDeep',
            'Resolved' => 'bg-success/10 text-success',
        ];
    @endphp

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
            {{ session('success') }}
        </div>
    @endif

    <x-tenant-panel title="Report a new issue" class="mb-6">
        <form action="{{ route('tenant.maintenance.store') }}" method="POST" class="space-y-4">
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

            <x-form-label for="title">Title</x-form-label>
            <x-form-input type="text" name="title" id="title" value="{{ old('title') }}" placeholder="e.g. Leaking faucet in the bathroom" required/>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-4">
                    <x-form-label for="category">Category</x-form-label>
                    <select name="category" id="category" class="{{ $fieldClass }}">
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-4">
                    <x-form-label for="priority">Priority</x-form-label>
                    <select name="priority" id="priority" class="{{ $fieldClass }}">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', 'Low') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <x-form-label for="description">Description</x-form-label>
            <textarea name="description" id="description" class="{{ $fieldClass }} resize-none min-h-[100px]" placeholder="Describe the problem, where it is, and when it started." required>{{ old('description') }}</textarea>

            <button type="submit" class="rounded-full bg-copper px-6 py-2 text-sm font-semibold text-white transition hover:bg-copperDeep">Submit request</button>
        </form>
    </x-tenant-panel>

    <x-tenant-panel :title="'Your requests ('. count($requests).')'">
        <div class="flex flex-col gap-3">
            @forelse ($requests as $request)
                <div class="rounded-xl border border-paper2 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-[11px] font-medium tracking-wide text-inkSoft">{{ $request->reference }}</span>
                        <span class="text-xs font-medium text-inkSoft">{{ $request->category }}</span>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $priorityClasses[$request->priority] ?? 'bg-paper2 text-inkSoft' }}">{{ $request->priority }} priority</span>
                        <span class="ml-auto rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClasses[$request->status] ?? 'bg-paper2 text-inkSoft' }}">{{ $request->status }}</span>
                    </div>

                    <h3 class="mt-2 font-display text-[17px] font-bold text-ink">{{ $request->title }}</h3>
                    <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-inkSoft">{{ $request->description }}</p>

                    @if ($request->admin_remarks)
                        <p class="mt-3 rounded-lg bg-paper2/60 px-3 py-2 text-xs text-inkSoft">
                            <span class="font-semibold text-ink">Management note:</span> {{ $request->admin_remarks }}
                        </p>
                    @endif

                    <p class="mt-3 text-xs text-inkSoft">
                        Submitted {{ $request->created_at->format('M j, Y · g:i A') }}
                        @if ($request->resolved_at)
                            · Resolved {{ $request->resolved_at->format('M j, Y') }}
                        @endif
                    </p>
                </div>
            @empty
                <div class="flex min-h-[120px] items-center justify-center text-sm text-inkSoft">
                    You haven't filed any request yet.
                </div>
            @endforelse
        </div>
    </x-tenant-panel>
</x-tenant-layout>