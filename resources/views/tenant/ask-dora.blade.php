<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Ask DORA - DORA</x-slot:title>

    <x-header title="Ask DORA" description="Your personal DORA response asssistant. "/>

    @php
    $suggestions = ['Explain my current bill', 'Help me write a maintenance request','What are the rules on visitors?'];
    @endphp

    <section class="flex h-[32rem] flex-col overflow-hidden rounded-2xl border border-paper2 bg-paper3">
        <div class="flex items-center gap-3 border-b border-paper2 px-5 py-4">
            <span class="text-3xl" aria-hidden="true">🤖</span>
            <!-- <img src="{{ asset('images/dora.svg') }}" alt="" class="h-10 w-10"> -->
            <div>
                <div class="text-sm font-semibold">DORA Assistant</div>
                <div class="text-xs">AI response assistant · Cozy Haven</div>
            </div>
        </div>

        <div class="flex flex-1 flex-col items-center justify-center gap-3 p-6">
            <span class="text-5xl" aria-hidden="true">🤖</span>
            <!-- <img src="{{ asset('images/dora.svg') }}" alt="" class="h-10 w-10"> -->
            <p>Ask DORA anything about your stay.</p>
        </div>

        <div class="flex flex-wrap gap-2 px-4 pb-3">
            @foreach ($suggestions as $suggestion)
            <button type="button" class="rounded-full bg-paper2 px-3.5 py-1.5 text-xs font-medium hover:bg-paper2/70">
                {{ $suggestion }}
            </button>
            @endforeach
        </div>

        <div class="flex gap-3 border-t border-paper2 p-4">
            <form action="" method="" class=""></form>
            <input type="text" class="flex-1 rounded-full border border-paper2 bg-white px-4 py-2.5 text-sm" placeholder="Type your message..." aria-label="Message">

            <button class="rounded-full bg-copper px-5 py-2.5 text-sm font-semibold text-white hover:bg-copperDeep" type="submit">Send</button>
        </div>
    </section>

</x-tenant-layout>