{{-- Edit room modal (one dialog, filled in by JavaScript) --}}
<dialog id="edit-room-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 28rem; max-height: 90vh; overflow-y: auto;"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 class="font-display text-xl font-bold text-ink">Edit room</h2>

    @if ($errors->editRoom->any())
        <div class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">
            @foreach ($errors->editRoom->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form id="edit-room-form" method="POST" action="" class="mt-4 flex flex-col gap-4">
        @csrf
        @method('PUT')
        <input type="hidden" name="editing_id" id="edit-editing-id">

        <label class="text-sm font-semibold text-ink">Room number
            <input id="edit-room-number" name="room_number" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Capacity
            <input id="edit-capacity" type="number" name="capacity" min="1" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Monthly rent (₱)
            <input id="edit-monthly-rent" type="number" step="0.01" name="monthly_rent" min="0" required
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Submeter number (optional)
            <input id="edit-submeter-number" name="submeter_number"
                   class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
        </label>

        <label class="text-sm font-semibold text-ink">Status
            <select id="edit-status" name="status"
                    class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal">
                <option value="available">Available</option>
                <option value="occupied">Occupied</option>
                <option value="reserved">Reserved</option>
            </select>
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft">Cancel</button>
            <button type="submit" class="rounded-xl bg-teal px-4 py-2 text-sm font-semibold text-white hover:bg-tealDeep">Save changes</button>
        </div>
    </form>
</dialog>

<script>
    const updateUrlTemplate = @js(route('admin.room.update', ['room' => '__ID__']));

    function openEditRoom(room) {
        document.getElementById('edit-room-form').action = updateUrlTemplate.replace('__ID__', room.id);
        document.getElementById('edit-editing-id').value = room.id ?? '';
        document.getElementById('edit-room-number').value = room.room_number ?? '';
        document.getElementById('edit-capacity').value = room.capacity ?? 1;
        document.getElementById('edit-monthly-rent').value = room.monthly_rent ?? '';
        document.getElementById('edit-submeter-number').value = room.submeter_number ?? '';
        document.getElementById('edit-status').value = room.status ?? 'available';
        document.getElementById('edit-room-modal').showModal();
    }
</script>

@if ($errors->editRoom->any())
    <script>
        openEditRoom({
            id: @js(old('editing_id')),
            room_number: @js(old('room_number')),
            capacity: @js(old('capacity')),
            monthly_rent: @js(old('monthly_rent')),
            submeter_number: @js(old('submeter_number')),
            status: @js(old('status')),
        });
    </script>
@endif