@props(['title','badge'=>null])

<section {{$attributes->merge(['class'=>'rounded-2xl border border-paper2 bg-paper3 p-6'])}}>
    <header class="flex items-center justify-between mb-4">
        <h1 class="font-display text-lg font-bold">{{$title}}</h1>
        @if ($badge)
        <span class="rounded-full bg-teal/10 px-3 py-3 text-xs font-semibold text-tealDeep">{{$badge}}</span>
        @endif
    </header>


    {{$slot}}

</section>