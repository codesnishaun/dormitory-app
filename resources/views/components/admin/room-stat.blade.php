{{-- Compact KPI card shared by the room totals at the top of the index. --}}
@props([
    'title',
    'value',
    'description',
    'tone' => 'ink',
])

@php
    $tones = [
        'ink' => ['border-ink/6', 'bg-ink/5', 'text-inkSoft', 'text-ink'],
        'success' => ['border-success/15', 'bg-success/10', 'text-success', 'text-success'],
        'copper' => ['border-copper/15', 'bg-copper/10', 'text-copperDeep', 'text-copperDeep'],
        'danger' => ['border-danger/15', 'bg-danger/10', 'text-danger', 'text-danger'],
    ];
    [$borderTone, $iconBackground, $iconTone, $valueTone] = $tones[$tone] ?? $tones['ink'];
@endphp

<div class="rounded-2xl border {{ $borderTone }} bg-paper3 p-4 shadow-[0_2px_10px_rgba(22,35,28,0.03)] sm:p-5">
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-medium text-inkSoft sm:text-sm">{{ $title }}</p>
        <span class="flex h-8 w-8 items-center justify-center rounded-xl {{ $iconBackground }} {{ $iconTone }}">
            {{ $slot }}
        </span>
    </div>
    <p class="mt-3 font-display text-2xl font-bold {{ $valueTone }} sm:text-3xl">{{ $value }}</p>
    <p class="mt-1 text-[11px] text-inkSoft">{{ $description }}</p>
</div>
