{{--    <x-button> — reusable button component
 
    Props:
      variant : primary | secondary | copper | teal | ghost | outline | danger   (default: primary)
      size    : md | sm                                                          (default: md)
      block   : true | false                                                     (default: false)
      href    : if set, renders as <a> instead of <button>
      type    : button | submit | reset  (only used when not href, default: button)
 
    primary / secondary are the pair used for nav bars and hero CTAs (solid, elevated copper
    vs. frosted-glass outline for dark backgrounds). copper/teal/ghost/outline/danger remain
    available for forms, dashboards, and destructive actions.
 
    Usage:
      <x-button>Apply for a room</x-button>                
      <x-button variant="secondary">Log in</x-button>
      <x-button variant="teal" size="sm">Record payment</x-button>
      <x-button variant="outline" block>Cancel</x-button>
      <x-button variant="danger" href="{{ route('logout') }}">Log out</x-button>
      <x-button type="submit">Save changes</x-button>  
--}}



@props([
    'variant' => 'primary',
    'size'    => 'md',
    'block'   => false,
    'href'    => null,
    'type'    => 'button',
    'disabled' => false,
    'active' => false,
])
 
@php
    $base = '
        inline-flex 
        items-center 
        justify-center 
        gap-2 
        rounded-full 
        font-semibold 
        tracking-wide 
        transition 
        active:scale-[0.97] 
        disabled:opacity-50 
        disabled:cursor-not-allowed';
 
    $variants = [
        'navigation' => '!rounded-xl border ' . 
                    ($active ? 'text-copper bg-paper/[0.06] border-copper/10' : 'text-paper/75 bg-transparent border-transparent hover:text-copper hover:bg-paper/[0.06] hover:border-copper/30'),
        'primary'    => 'bg-copper text-white hover:bg-copperDeep hover:-translate-y-px hover:text-paper',
        'secondary'  => 'bg-paper/[0.08] text-paper border border-paper/[0.18] backdrop-blur-sm hover:bg-paper/[0.16] hover:border-paper/30',
        'copper'     => 'bg-copper text-white hover:bg-copperDeep',
        'teal'       => 'bg-teal text-white hover:bg-tealDeep',
        'ghost'      => 'bg-transparent text-paper border border-paper/35 hover:border-paper',
        'outline'    => 'bg-transparent text-ink border border-ink/[0.14] hover:border-ink',
        'danger'     => 'bg-transparent text-danger border border-danger/40 hover:bg-danger/[0.08]',
    ];
 
    $sizes = [
        'md' => 'px-[22px] py-3 text-[14.5px]',
        'sm' => 'px-3.5 py-1.5 text-[13px]',
    ];
 
    $classes = trim(implode(' ', [
        $base,
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
    ]));
@endphp
 
@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => $classes]) }}
    >{{ $slot }}</a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled)
        {{ $attributes->merge(['class' => $classes]) }}
    >{{ $slot }}</button>
@endif
