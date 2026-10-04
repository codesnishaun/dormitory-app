@props([
    'bodyClass' => 'min-h-screen bg-ink font-sans text-paper',
])

<x-app navigation="admin" :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'Admin - DORA' }}</x-slot:title>

    <x-slot:sidebar>
        @if (isset($navigation) && ! $navigation->isEmpty())
            {{ $navigation }}
        @else
            <p class="hidden px-3 pb-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-paper/40 md:block">
                Management
            </p>

            <a
                href="{{ route('admin.dashboard') }}"
                aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}"
                @class([
                    'flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors',
                    'bg-copper text-ink' => request()->routeIs('admin.dashboard'),
                    'text-paper/65 hover:bg-paper/10 hover:text-paper' => ! request()->routeIs('admin.dashboard'),
                ])
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
                    <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
                    <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
                    <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
                </svg>
                <span>Dashboard</span>
            </a>

            <a
                href="{{ route('home') }}"
                class="flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-paper/65 transition-colors hover:bg-paper/10 hover:text-paper"
            >
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.5 10 8.5-7 8.5 7v9.5a1 1 0 0 1-1 1h-5v-6h-5v6h-5a1 1 0 0 1-1-1V10Z" />
                </svg>
                <span>View public site</span>
            </a>
        @endif
    </x-slot:sidebar>

    {{ $slot }}
</x-app>
