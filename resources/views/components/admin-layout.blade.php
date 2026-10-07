@props([
    'bodyClass' => 'h-dvh overflow-hidden bg-paper font-sans text-ink',
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
                ><x-svg-icon name="dashboard" />Dashboard</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('admin.tenant.index')"
                    :active="request()->routeIs('admin.tenant.index')"
                ><x-svg-icon name="tenants" />Tenants</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('admin.room.index')"
                    :active="request()->routeIs('admin.room.index')"
                ><x-svg-icon name="rooms" />Rooms &amp; Meters</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('admin.billing.index')"
                    :active="request()->routeIs('admin.billing.index')"
                ><x-svg-icon name="billing" />Billing</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('admin.announcement.index')"
                    :active="request()->routeIs('admin.announcement.index')"
                ><x-svg-icon name="announcements" />Announcements</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('admin.maintenance.index')"
                    :active="request()->routeIs('admin.maintenance.index')"
                ><x-svg-icon name="maintenance" />Maintenance</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                ><x-svg-icon name="assistant" />Ask DORA</x-button>
                
            </div>

            <form action="{{ route('logout') }}" method="POST" class="mt-auto border-t border-paper/10 pt-4">
                @csrf
                <x-button
                    variant="sidebar"
                    type="submit"
                    :active="request()->routeIs('home')"
                ><x-svg-icon name="logout" />Logout</x-button>
            </form>
        @endif
    </x-slot:sidebar>

    {{ $slot }}
</x-app>
