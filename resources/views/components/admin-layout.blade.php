@props([
    'bodyClass' => 'min-h-screen bg-paper font-sans text-ink',
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
                    :href="route('admin.tenant.index')"
                    :active="request()->routeIs('admin.tenant.index')"
                >Tenants</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('admin.room.index')"
                    :active="request()->routeIs('admin.room.index')"
                >Rooms &amp; Meters</x-button>

                {{-- <x-button
                    variant="sidebar"
                    :href="route('admin.billing.index')"
                    :active="request()->routeIs('admin.billing.index')"
                >Biling</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('admin.announcement.index')"
                    :active="request()->routeIs('admin.announcement.index')"
                >Announcement</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('admin.maintenance.index')"
                    :active="request()->routeIs('admin.maintenance.index')"
                >Maintenance</x-button> --}}

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Ask DORA</x-button>
                
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
