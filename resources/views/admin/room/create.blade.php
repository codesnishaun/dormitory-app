{{-- Add room modal --}}
<dialog id="add-room-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 28rem; max-height: 90vh; overflow-y: auto;"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 class="font-display text-xl font-bold text-ink">Add room</h2>

    @if ($errors->any())
        <div class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.room.store') }}" class="mt-4 flex flex-col gap-4">
        @csrf

        <label class="text-sm font-semibold text-ink">Room number
            <input name="room_number" value="{{ old('room_number') }}" required placeholder="e.g. R205"
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Capacity
            <input type="number" name="capacity" value="{{ old('capacity', 1) }}" min="1" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Monthly rent (₱)
            <input type="number" step="0.01" name="monthly_rent" value="{{ old('monthly_rent') }}" min="0" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Submeter number (optional)
            <input name="submeter_number" value="{{ old('submeter_number') }}"
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft">Cancel</button>
            <button type="submit" class="rounded-xl bg-teal px-4 py-2 text-sm font-semibold text-white hover:bg-tealDeep">Save room</button>
        </div>
    </form>
</dialog>

@if ($errors->any())
    <script>document.getElementById('add-room-modal').showModal();</script>
@endif