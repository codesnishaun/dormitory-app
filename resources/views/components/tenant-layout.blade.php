@props([
    'bodyClass' => 'min-h-screen bg-ink font-sans text-paper',
])

<x-app navigation="tenant" :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'Tenant - DORA' }}</x-slot:title>

    <x-slot:sidebar>
        {{ $navigation ?? '' }}
    </x-slot:sidebar>

    {{ $slot }}
</x-app>
