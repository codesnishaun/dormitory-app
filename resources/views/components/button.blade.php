
@props([
    'variant' => 'copper',
    'type' => 'button',
    'disabled' => false,
])

@php
    $variants = [
        'copper' => 'bg-copper text-white border border-transparent hover:bg-copperDeep',
        'teal' => 'bg-teal text-white border border-transparent hover:bg-tealDeep',
        'subtle' => 'bg-transparent text-paper border border-paper/35 hover:border-paper',
        'outline' => 'bg-transparent text-ink border border-ink/[0.14] hover:border-ink',
        'danger' => 'bg-transparent text-danger border border-danger/40 hover:bg-danger/[0.08]',
    ];
@endphp

<button
    type="{{ $type }}"
    @disabled($disabled)
    {{ $attributes->merge([
        'class' => "
            inline-flex items-center justify-center gap-2
            px-[22px] py-3
            rounded-full
            font-semibold text-[14.5px]
            tracking-[0.01em]
            transition-[transform,box-shadow,background]
            duration-120 ease-out
            active:scale-[0.97]
            disabled:opacity-50
            disabled:cursor-not-allowed
            {$variants[$variant]}
        "
    ]) }}
>
    {{ $slot }}
</button>

