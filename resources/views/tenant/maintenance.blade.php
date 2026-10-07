<x-tenant-layout body-class="min-h-screen font-sans text-ink">
    <x-slot:title>Maintenance  - DORA</x-slot:title>
    <x-header title="Maintenance" description="Report an issue or check your request status."/>

    @php
    $requests=[];
    @endphp

    <x-tenant-panel title="Report a new issue" class="mb-6">
        <form action="#" method="POST" class="space-y-4">
            @csrf
            <x-form-label for="" >Title</x-form-label>
            <x-form-input type="text" name="title" id="title" placeholder="Ingay ng kadormmit ko" required/>

            <x-form-label for="" >Priority</x-form-label>
            <select name="" id="" class="w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper">
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>

            <x-form-label for="">Description</x-form-label>
            <textarea name="" id="" class="w-full rounded-xl border border-paper2 bg-white px-3 py-2 text-sm text-ink outline-none focus:border-copper focus:ring-1 focus:ring-copper resize-none min-h-[100px]" placeholder="wkwkwkkwkwkkwkwkwkwk" required></textarea>

            <button type="submit" class="rounded-full bg-copper px-6 py-2 text-sm font-semibold text-white transition hover:bg-copperDeep">Submit request</button>
        </form>
    </x-tenant-panel>

    <x-tenant-panel :title="'Your requests ('. count($requests).')'">
        @forelse ($requests as $request)

        @empty
        <div class="flex min-h-[120px] items-center justify-center text-sm text-inkSoft">
            You haven't filed any request yet.
        </div>
        @endforelse
    </x-tenant-panel>



</x-tenant-layout>