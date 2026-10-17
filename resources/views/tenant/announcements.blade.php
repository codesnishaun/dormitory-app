<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Announcements - DORA</x-slot:title>

    <x-header title="Announcements" description="Notices from Cozy Haven management."/>

    <x-tenant-panel>
        <div class="flex flex-col gap-3">
            @forelse ($announcements as $announcement)
                <div class="rounded-xl border p-4 {{ $announcement->is_pinned ? 'border-copper bg-copper/10' : 'border-paper2' }}">
                    <div class="font-display text-[17px] font-bold text-ink">
                        @if ($announcement->is_pinned)
                            📌
                        @endif
                        {{ $announcement->title }}
                    </div>
                    <div class="text-xs font-sans text-inkSoft">
                        {{ $announcement->category }} · {{ $announcement->publish_at->format('F j, Y') }} · {{ $announcement->author_name ?? 'Admin' }}
                    </div>
                    <div class="mt-1 whitespace-pre-line font-sans text-sm leading-relaxed text-inkSoft">{{ $announcement->body }}</div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-inkSoft">Wala pang announcement.</p>
            @endforelse
        </div>
    </x-tenant-panel>
</x-tenant-layout>