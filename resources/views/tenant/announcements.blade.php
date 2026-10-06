<x-tenant-layout class="min-h-screen font-sans text-ink">
    <x-slot:title>Announcements - DORA</x-slot:title>

    <x-header title="Announcments" description="Notices from Cozy Haven management."/>

    @php
    $announcements=[
    [true, 'Scheduled water interruption - Aug. 30','2026-08-27','Admin','Pakisabi sa mga kapitbahay.'],
    [false, 'Monthly house meeting - Sept. 5','2026-08-24','Admin','Avisala'],
    [false, 'Wifi router upgrade completed','2026-08-18','Admin','Wifi ni Peter']];
    @endphp

    <x-tenant-panel>
        <div class="flex flex-col gap-3">
            @foreach ($announcements as [$pinned,$title,$date,$author,$body])
            <div class="rounded-xl border p-4 {{$pinned ? 'border-copper bg-copper-10' : 'border-paper2'}}">
                <div class="font-display text-[17px] font-bold text-ink">
                    
                    @if ($pinned)
                    📌
                    @endif
                    {{$title}}
                </div>
                <div class="text-xs font-sans text-inkSoft">{{$date}} · {{$author}}</div>
                <div class="font-sans text-sm leading-relaxed text-inkSoft">{{$body}}</div>
            </div>
            @endforeach
        </div>
    </x-tenant-panel>
</x-tenant-layout>