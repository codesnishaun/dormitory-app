@props([
    'bodyClass' => 'min-h-screen bg-ink font-sans text-paper',
])

<x-app navigation="tenant" :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'Tenant - DORA' }}</x-slot:title>

    <x-slot:sidebar>
        @if (isset($navigation) && ! $navigation->isEmpty())
            {{ $navigation }}
        @else
            <div class="mb-3 flex items-center gap-3 rounded-2xl border border-paper/10 bg-paper/[0.05] p-3 md:mb-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal/20 font-display text-lg font-bold text-[#A8D2C9]">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-paper">{{ auth()->user()->name }}</div>
                    <div class="mt-0.5 truncate text-[11px] text-paper/50">Tenant account</div>
                </div>
                <span class="ml-auto rounded-full bg-teal/15 px-2 py-1 text-[9px] font-bold uppercase tracking-[0.12em] text-[#A8D2C9]">Tenant</span>
            </div>

            <div class="flex flex-col gap-3">
                <section class="flex flex-col gap-1" aria-labelledby="tenant-navigation-overview">
                    <h2 id="tenant-navigation-overview" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Overview</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('tenant.dashboard')"
                        :active="request()->routeIs('tenant.dashboard')"
                    ><x-svg-icon name="dashboard" />Dashboard</x-button>
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="tenant-navigation-stay">
                    <h2 id="tenant-navigation-stay" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">My stay</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('tenant.room-bill')"
                        :active="request()->routeIs('tenant.room-bill')"
                    ><x-svg-icon name="rooms" />My room &amp; bill</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('tenant.absence-request')"
                        :active="request()->routeIs('tenant.absence-request')"
                    ><x-svg-icon name="tenants" />Absence requests</x-button>
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="tenant-navigation-services">
                    <h2 id="tenant-navigation-services" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Services</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('tenant.maintenance')"
                        :active="request()->routeIs('tenant.maintenance')"
                    ><x-svg-icon name="maintenance" />Maintenance</x-button>
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="tenant-navigation-communication">
                    <h2 id="tenant-navigation-communication" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Communication</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('tenant.announcements')"
                        :active="request()->routeIs('tenant.announcements')"
                    ><x-svg-icon name="announcements" />Announcements</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('tenant.ask-dora')"
                        :active="request()->routeIs('tenant.ask-dora')"
                    ><x-svg-icon name="assistant" />Ask DORA</x-button>
                </section>
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
