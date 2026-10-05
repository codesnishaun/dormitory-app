@props([
    'bodyClass' => 'min-h-screen bg-ink font-sans text-paper',
])

<x-app navigation="admin" :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'Admin - DORA' }}</x-slot:title>

    <x-slot:sidebar>
        @if (isset($navigation) && ! $navigation->isEmpty())
            {{ $navigation }}
        @else
            <div class="mb-2 rounded-xl bg-paper/10 px-4 py-3 md:mb-4">
                <div class="text-sm font-semibold text-paper">Byron &amp; Aaron</div>
                <div class="text-[11px] uppercase tracking-widest text-paper/55">Administrator</div>
            </div>
            
            <div class="flex flex-col gap-1">
                <x-button
                    variant="sidebar"
                    :href="route('admin.dashboard')"
                    :active="request()->routeIs('admin.dashboard')"
                >Dashboard</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('application_form')"
                    :active="request()->routeIs('application_form')"
                >Tenants</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Rooms &amp; Meters</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Biling</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Announcement</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Maintenance</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Maintenance</x-button>
                
            </div>

            <x-button
                variant="sidebar"
                :href="route('home')"
                class="mt-auto border-t border-paper/10 pt-4"
                :active="request()->routeIs('home')"
            >Logout</x-button>
            
        @endif
    </x-slot:sidebar>

    {{ $slot }}
</x-app>
