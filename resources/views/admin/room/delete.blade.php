{{-- Delete room confirmation --}}
<dialog id="delete-room-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 26rem;"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 class="font-display text-xl font-bold text-ink">Delete room?</h2>
    <p class="mt-2 text-sm text-inkSoft">
        You are about to delete <strong id="delete-room-name" class="text-ink"></strong>. This can't be undone.
    </p>

    <form id="delete-room-form" method="POST" action="" class="mt-5 flex justify-end gap-2">
        @csrf
        @method('DELETE')
        <button type="button" onclick="this.closest('dialog').close()"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft">Cancel</button>
        <button type="submit" class="rounded-xl bg-danger px-4 py-2 text-sm font-semibold text-white">Delete</button>
    </form>
</dialog>

<script>
    const destroyUrlTemplate = @js(route('admin.room.destroy', ['room' => '__ID__']));

    function openDeleteRoom(id, number) {
        document.getElementById('delete-room-form').action = destroyUrlTemplate.replace('__ID__', id);
        document.getElementById('delete-room-name').textContent = 'Room ' + number;
        document.getElementById('delete-room-modal').showModal();
    }
</script>