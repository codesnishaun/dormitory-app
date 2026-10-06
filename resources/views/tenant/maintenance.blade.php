<x-tenant-layout class="min-h-screen font-sans text-ink">
    <x-slot:title>Maintenance  - DORA</x-slot:title>
    <x-header title="Maintenance" description="Report an issue or check your request status."/>

    @php
    $request=0;
    @endphp

    <x-tenant-panel title="Report a new issue" class="mb-6">
        <form action="" method="" class="space-y-4">
            <label for="" class="block text-xs font-semibold uppercase tracking-wide text-ink">Title</label>
            <input type="text" class="w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper" placeholder="Ingay ng kadormmit ko">

            <label for="" class="block text-xs font-semibold uppercase tracking-wide text-ink">Priority</label>
            <select name="" id="" class="w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper">
                <option value="Low" class="">Low</option>
                <option value="Medium" class="">Medium</option>
                <option value="High" class="">High</option>
            </select>

            <label for="" class="block text-xs font-semibold uppercase tracking-wide text-ink">Description</label>
            <textarea name="" id="" class="w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper resize-none min-h-[100px]" placeholder="wkwkwkkwkwkkwkwkwkwk"></textarea>

            <button type="submit" class="rounded-full bg-copper px-6 py-2 text-sm font-semibold text-white transition hover:opacity-90">Submit request</button>
        </form>
    </x-tenant-panel>

    <x-tenant-panel title="Your request ({{$request}})">
        <div class="flex min-h-[120px] items-center justify-center text-sm text-inkSoft">
            You haven't filled any request yet.
        </div>
    </x-tenant-panel>



</x-tenant-layout>