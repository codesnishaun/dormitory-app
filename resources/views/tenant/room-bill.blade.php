<x-tenant-layout body-class="min-h-screen bg-paper font-sans text-ink">
    <x-slot:title>My room & bill - DORA</x-slot:title>

    <x-slot:navigation>
        <x-tenant-sidebar active="bill" />
    </x-slot:navigation>

    <header class="mb-7"> 
        <h1 class="font-display text-3xl font-bold">My Room and Bill</h1>
        <p class="mt-1 text-sm text-inkSoft">Your room details and current sub-meter billing.</p>
    </header>
    </header>

    <div class="flex flex-col gap-5">
        <x-tenant-panel title="Room R101" badge="Single">
            {{-- 3 rows dito --}}
        </x-tenant-panel>
    </div>
</x-tenant-layout>