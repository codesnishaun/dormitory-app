{{-- Cream ang content area; ang sidebar ay nananatiling bg-ink galing sa app.blade.php --}}
<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Home - DORA</x-slot:title>

    {{-- Page content --}}
    <header class="mb-7">
        <h1 class="font-display text-3xl font-bold">Home</h1>
        <p class="mt-1 text-sm text-inkSoft">Welcome back — here is what is new at Cozy Haven.</p>
    </header>

    {{-- Placeholder data. Papalitan ng backend ng totoong values. --}}
    <section class="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Room', 'R101', 'Single'],
            ['Est. bill this cycle', '₱3,944.00', '₱3,500.00 rent + ₱444.00 elec.'],
            ['Open repair requests', '0', 'None open'],
            ['Outstanding balance', '₱0.00', 'Cleared'],
        ] as [$label, $value, $note])
            <article class="rounded-2xl border border-paper2 bg-paper3 p-5">
                <div class="text-[11px] font-semibold uppercase tracking-widest">{{ $label }}</div>
                <div class="mt-1.5 font-display text-3xl font-semibold leading-tight">{{ $value }}</div>
                <div class="mt-1.5 text-xs font-medium text-success">{{ $note }}</div>
            </article>
        @endforeach
    </section>

    <section class="grid items-start gap-5 lg:grid-cols-[1.55fr_1fr]">
        <article class="rounded-2xl border border-paper2 bg-paper3 p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-bold">Latest announcement</h2>
                <a href="#" class="rounded-full border border-paper2 bg-white px-4 py-1.5 text-[13px] font-semibold hover:bg-paper2/60">View all</a>
            </div>

            <div class="rounded-xl border border-copper bg-copper/10 p-4">
                <h3 class="font-display text-[17px] font-bold">📌 Scheduled water interruption — Aug 30</h3>
                <time datetime="2026-08-27" class="mt-1 block text-xs text-inkSoft">2026-08-27</time>
                <p class="mt-2 text-sm leading-relaxed text-inkSoft">
                    The Bulacan water district has advised a scheduled interruption in our barangay from 9 AM to 3 PM this Sunday for pipeline maintenance. Please store water in advance. The dorm reservoir will supply comfort rooms during this window.
                </p>
            </div>
        </article>

        <article class="rounded-2xl border border-paper2 bg-paper3 p-6">
            <h2 class="mb-4 font-display text-lg font-bold">Quick actions</h2>
            <div class="flex flex-col gap-2.5">
                <a href="#" class="rounded-full border border-paper2 bg-white px-5 py-3.5 text-center text-sm font-semibold hover:bg-paper2/60">🛠 Report an issue</a>
                <a href="#" class="rounded-full border border-paper2 bg-white px-5 py-3.5 text-center text-sm font-semibold hover:bg-paper2/60">⏻ View meter reading</a>
                <a href="#" class="rounded-full border border-teal bg-teal px-5 py-3.5 text-center text-sm font-semibold text-white hover:bg-tealDeep">✳ Ask DORA a question</a>
            </div>
        </article>
    </section>
</x-tenant-layout>
