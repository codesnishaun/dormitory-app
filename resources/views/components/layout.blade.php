@props([
    'bodyClass' => '',
])

<x-app :body-class="$bodyClass">
    <x-slot:title>{{ $title ?? 'DORA' }}</x-slot:title>

    {{ $slot }}
</x-app>