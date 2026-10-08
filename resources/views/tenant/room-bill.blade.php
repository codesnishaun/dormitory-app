<x-tenant-layout body-class="min-h-screen bg-paper font-sans text-ink">
    <x-slot:title>My room &amp; bill - DORA</x-slot:title>

    <header class="mb-7"> 
        <h1 class="font-display text-3xl font-bold">My Room and Bill</h1>
        <p class="mt-1 text-sm text-inkSoft">Your room details and current sub-meter billing.</p>
    </header>

    @php
        $roomDetails = [
        ['🏠','Monthly rent', '₱3,500.00'],
        ['🛏️', 'Roommates', 'You have this room to yourself'],
        ['📆', 'Move-in date', '2026-11-10']];
        $pastmeter = [
        ['2026-07-01','1140 -> 1182', '42', '₱504.00'],
        ['2026-06-01','1098 -> 1140', '42', '₱504.00']];
        $payments = [
        ['Payment','2026-08-01','G-cash','July rent + electric','-₱3,920.00']];
        $percent = 62;
        $prevReading = 1182;
        $currReading = 1219;
        $kwh = $currReading - $prevReading;   // 37, kinukuwenta na
        $rate = '₱12.00';
        $lastUpdated = '2026-08-01';
        $rent = '₱3,500.00';
        $electric = '₱444.00';
        $total = '₱3,944.00';
    @endphp

    <div class="flex flex-col gap-5">  
        <x-tenant-panel title="Room R101" badge="Single">
            @foreach ($roomDetails as [$icon,$label,$value])
                <div class="flex gap-3 border-b border-paper2 py-3 last:border-0">
                    <span aria-hidden="true">{{$icon}}</span>
                    <div>
                        <div class="text-sm font-semibold">{{$label}}</div>
                        <div class="text-[13px] text-inkSoft">{{$value}}</div>  
                    </div>
                </div>
            @endforeach
        </x-tenant-panel>
        <x-tenant-panel title="Sub-meter & Electric bill">
            <div class="flex items-center gap-4 rounded-xl bg-paper2 p-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full"
                style="background: conic-gradient(var(--color-copper) {{ $percent }}%, rgba(22,35,28,.12) 0)">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-paper3 text-[11px] font-semibold">
                        {{ $percent }}%
                    </div>
                </div>
                
                <div>
                    <div class="font-mono text-[15px] font-semibold">{{ $kwh }} kWh consumed this cycle</div>
                    <div class="text-xs text-inkSoft">
                        Previous reading: <strong>{{ $prevReading }}</strong> · Current reading: <strong>{{ $currReading }}</strong>
                    </div>
                    <div class="text-xs text-inkSoft">Rate: {{ $rate }}/kWh · Last updated {{ $lastUpdated }}</div>
                </div>
            </div>

            <div class="mt-3 grid grid-cols-3 rounded-xl bg-paper2 p-4">
                <div>
                    <div class="text-xs text-inkSoft">Rent</div>
                    <div class="font-mono text-lg font-semibold">{{$rent}}</div>
                </div>
                <div>
                    <div class="text-xs text-inkSoft">Electric</div>
                    <div class="font-mono text-lg font-semibold">{{$electric}}</div>
                </div>
                <div>
                    <div class="text-right">
                        <div class="text-xs text-inkSoft">Total due</div>
                        <div class="font-mono text-lg font-semibold text-copperDeep">{{$total}}</div>
                    </div>
                </div>
            </div>
        </x-tenant-panel>
        <x-tenant-panel title="Past meter cycles">
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tr class="border-b border-paper2">
                        <th class="py-3 text-xs uppercase tracking-widest">Cycle Date</th>
                        <th class="py-3 text-xs uppercase tracking-widest">Prev->Curr</th>
                        <th class="py-3 text-xs uppercase tracking-widest">KwH</th>
                        <th class="py-3 text-xs uppercase tracking-widest">Amount</th>
                    </tr>
                    @foreach ($pastmeter as [$cycledate,$prev_curr,$kwH,$amount])
                    <tr class="border-b border-paper2 last:border-0">          
                        <td class="font-mono py-3">
                            {{$cycledate}}
                        </td>
                        <td class="font-mono py-3">
                            {{$prev_curr}}
                        </td>
                        <td class="font-mono py-3">
                            {{$kwH}}
                        </td>
                        <td class="font-mono py-3">
                            {{$amount}}
                        </td>    
                    </tr>
                    @endforeach
                </table>
            </div>
        </x-tenant-panel>
        <x-tenant-panel title="Your Payment History" badge="Settled">
            @foreach ($payments as [$name,$date,$mop,$note,$amount])
            <div class="rounded-xl border border-paper2 p-4 flex justify-between">
                <div>
                    <div class="text-lg font-semibold">{{$name}}</div>
                    <div class="text-xs text-inkSoft">{{$date}} · {{$mop}}</div>
                    <div class="mt-3">{{$note}}</div>
                </div>
                <div class="font-mono font-semibold text-success">{{$amount}}</div>    
            </div>
            @endforeach

        </x-tenant-panel>
    </div>
</x-tenant-layout>