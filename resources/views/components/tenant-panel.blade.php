@props(['title'=>null,'badge'=>null])

<section {{$attributes->merge(['class'=>'rounded-2xl border border-paper2 bg-paper3 p-6'])}}>
    <header class="flex items-center justify-between mb-4">
        @if ($title)
        <h1 class="font-display text-lg font-bold text-ink">{{$title}}</h1>
        @endif
        @if ($badge)
        <span class="rounded-full bg-teal/10 px-3 py-1 text-xs font-semibold text-tealDeep">{{$badge}}</span>
        @endif
    </header>


    {{$slot}}

</section>