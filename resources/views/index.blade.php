<x-layout>
    <x-slot:title>
        Home Page
    </x-slot:title>
    
    <section class="relative overflow-hidden px-[6vw] pt-[9vw] pb-[6vw] grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-5 items-center">
        <div class="absolute inset-0 pointer-events-none opacity-50 bg-[radial-gradient(circle_at_15%_20%,rgba(193,113,47,0.16),transparent_40%),radial-gradient(circle_at_85%_75%,rgba(47,111,110,0.20),transparent_45%)]"></div>
        
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 text-copper text-xs font-semibold tracking-[0.12em] uppercase mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-copper"></span> San Ildefonso, Bulacan
            </div>
            <h1 class="font-display text-[clamp(2.4rem,4.6vw,4rem)] leading-[1.03] tracking-tight text-paper max-w-[17ch]">
                Your dormitory, <em class="text-copper italic">metered, monitored,</em> and one message away.
            </h1>
            <p class="mt-5 text-[17px] leading-relaxed text-paper/70 max-w-[46ch]">
                DORA keeps Cozy Haven's tenants, rooms, sub-meters, announcements, and repairs in one place — with an AI assistant on call for both management and residents.
            </p>
        
            <div class="flex flex-wrap gap-3.5 mt-8">
                <button class="px-6 py-3 rounded-full bg-copper text-white font-semibold text-sm hover:bg-copperDeep transition">Apply for a room</button>
                <button class="px-6 py-3 rounded-full border border-paper/35 text-paper font-semibold text-sm hover:border-paper transition">Log in to your account</button>
            </div>
        
            <div class="flex flex-wrap gap-7 mt-11">
            <div><b class="block font-display text-2xl text-paper">8</b><span class="text-xs uppercase tracking-wide text-paper/50">Rooms</span></div>
            <div><b class="block font-display text-2xl text-paper">7</b><span class="text-xs uppercase tracking-wide text-paper/50">Tenants</span></div>
            <div><b class="block font-display text-2xl text-paper">2</b><span class="text-xs uppercase tracking-wide text-paper/50">Open slots</span></div>
            </div>
        
            <button onclick="document.getElementById('map-section').scrollIntoView({behavior:'smooth'})"
            class="mt-5 text-sm text-paper/70 hover:text-paper transition">📍 View location</button>
        </div>

        <div class="flex h-100 w-full rounded-lg bg-gray-400 items-center justify-center animate-pulse text-gray-600">
            Map placeholder
        </div>
    </section>

</x-layout>