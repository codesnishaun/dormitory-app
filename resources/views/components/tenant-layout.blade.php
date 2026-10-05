@props([
    'bodyClass' => 'min-h-screen bg-ink font-sans text-paper',
])

<x-app navigation="tenant" :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'Tenant - DORA' }}</x-slot:title>

    <x-slot:sidebar>
        @if (isset($navigation) && ! $navigation->isEmpty())
            {{ $navigation }}
        @else
            <div class="mb-2 rounded-xl bg-paper/10 px-4 py-3 md:mb-4">
                <div class="text-sm font-semibold text-paper">Marianne Santos</div>
                <div class="text-[11px] uppercase tracking-widest text-paper/55">Room R101</div>
            </div>

            <div class="flex flex-col gap-1">
                <x-button
                    variant="sidebar"
                    :href="route('tenant.dashboard')"
                    :active="request()->routeIs('tenant.dashboard')"
                >Dashboard</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('application_form')"
                    :active="request()->routeIs('application_form')"
                >My room &amp; bill</x-button>
                
                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Announcements</x-button>

                <x-button
                    variant="sidebar"
                    :href="route('home')"
                    :active="request()->routeIs('home')"
                >Maintenance</x-button>

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
