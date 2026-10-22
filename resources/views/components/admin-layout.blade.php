@props([
    'bodyClass' => 'h-dvh overflow-hidden bg-paper font-sans text-ink',
])

<x-app navigation="admin" :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'Admin - DORA' }}</x-slot:title>

    <x-slot:sidebar>
        @if (isset($navigation) && ! $navigation->isEmpty())
            {{ $navigation }}
        @else
            <div class="mb-3 flex items-center gap-3 rounded-2xl border border-paper/10 bg-paper/[0.05] p-3 md:mb-6">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-copper/15 font-display text-lg font-bold text-copper">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-semibold text-paper">{{ auth()->user()->name }}</div>
                    <div class="mt-0.5 truncate text-[11px] text-paper/50">{{ auth()->user()->email }}</div>
                </div>
                <span class="ml-auto rounded-full bg-copper/15 px-2 py-1 text-[9px] font-bold uppercase tracking-[0.12em] text-copper">Admin</span>
            </div>
            
            <div class="flex flex-col gap-3">
                <section class="flex flex-col gap-1" aria-labelledby="admin-navigation-overview">
                    <h2 id="admin-navigation-overview" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Overview</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.dashboard')"
                        :active="request()->routeIs('admin.dashboard')"
                    ><x-svg-icon name="dashboard" />Dashboard</x-button>
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="admin-navigation-dormitory">
                    <h2 id="admin-navigation-dormitory" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Dormitory management</h2>
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
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="admin-navigation-utilities">
                    <h2 id="admin-navigation-utilities" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Utilities &amp; billing</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.submeter.index')"
                        :active="request()->routeIs('admin.submeter.index')"
                    ><x-svg-icon name="rooms" />Submeters</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.meter-assignments.index')"
                        :active="request()->routeIs('admin.meter-assignments.*', 'admin.meter-readings.*')"
                    ><x-svg-icon name="rooms" />Meter assignments</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.billing.index')"
                        :active="request()->routeIs('admin.billing.index')"
                    ><x-svg-icon name="billing" />Billing</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.electricity-bills.index')"
                        :active="request()->routeIs('admin.electricity-bills.*')"
                    ><x-svg-icon name="billing" />Electricity bills</x-button>
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="admin-navigation-operations">
                    <h2 id="admin-navigation-operations" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Operations</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.maintenance.index')"
                        :active="request()->routeIs('admin.maintenance.index')"
                    ><x-svg-icon name="maintenance" />Maintenance</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.absence-request.index')"
                        :active="request()->routeIs('admin.absence-request.*')"
                    ><x-svg-icon name="tenants" />Absence requests</x-button>
                </section>

                <section class="flex flex-col gap-1 border-t border-paper/10 pt-3" aria-labelledby="admin-navigation-communication">
                    <h2 id="admin-navigation-communication" class="px-3.5 pb-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/40">Communication</h2>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.announcement.index')"
                        :active="request()->routeIs('admin.announcement.index')"
                    ><x-svg-icon name="announcements" />Announcements</x-button>
                    <x-button
                        variant="sidebar"
                        :href="route('admin.ask-dora')"
                        :active="request()->routeIs('admin.ask-dora')"
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
