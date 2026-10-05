@props(['active'=>'home'])

<div class="mb-2 rounded-xl bg-paper/10 px-4 py-3 md:mb-4">
    <div class="text-sm font-semibold text-paper">Marianne Santos</div>
     <div class="text-[11px] uppercase tracking-widest text-paper/55">Room R101</div>
</div>

<a href="{{ url('tenant/dashboard')}}" 
    @if ($active === 'home') aria-current="page" @endif
    class="flex items-center gap-3 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium {{$active === 'home' ? 'bg-copper text-paper' : 'text-paper/70 hover:bg-paper/10 hover:text-paper'}}">
    <span aria-hidden="true">◧</span> Home
</a>
<a href="{{ url('tenant/room-bill')}}" 
    @if ($active === 'bill') aria-current="page" @endif
    class="flex items-center gap-3 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium {{$active === 'bill' ? 'bg-copper text-paper' : 'text-paper/70 hover:bg-paper/10 hover:text-paper'}}">
    <span aria-hidden="true">⏻</span> My room &amp; bill
</a>
<a href="#" 
    @if ($active ==='announcement') aria-current="page" @endif
    class="flex items-center gap-3 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium {{$active === 'announcement' ? 'bg-copper text-paper' : 'text-paper/70 hover:bg-paper/10 hover:text-paper'}}">
    <span aria-hidden="true">📣</span> Announcements
</a>
<a href="#" 
    @if ($active=== 'maintenance') aria-current="page" @endif
    class="flex items-center gap-3 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium {{$active === 'maintenance' ? 'bg-copper text-paper' : 'text-paper/70 hover:bg-paper/10 hover:text-paper'}}">
    <span aria-hidden="true">🛠</span> Maintenance
</a>
<a href="#" 
    @if ($active=== 'dora') aria-current="page" @endif
    class="flex items-center gap-3 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium {{$active === 'dora' ? 'bg-copper text-paper' : 'text-paper/70 hover:bg-paper/10 hover:text-paper'}}">
    <span aria-hidden="true">✳</span> Ask DORA
</a>
<a href="#" class="flex items-center gap-3 whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-medium text-paper/70 hover:bg-paper/10 hover:text-paper md:mt-6 md:border-t md:border-paper/10 md:pt-5">
    <span aria-hidden="true">↩</span> Log out
</a>