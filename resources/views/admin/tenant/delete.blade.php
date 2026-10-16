<dialog id="delete-tenant-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 26rem;"
    aria-labelledby="delete-tenant-heading"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 id="delete-tenant-heading" class="font-display text-xl font-bold text-ink">Delete tenant?</h2>
    <p class="mt-2 text-sm text-inkSoft">
        Delete <strong id="delete-tenant-name" class="text-ink"></strong>? This action cannot be undone.
        Tenants with room assignment history cannot be deleted.
    </p>

    <form id="delete-tenant-form" method="POST" action="" class="mt-5 flex justify-end gap-2">
        @csrf
        @method('DELETE')
        <button type="button" onclick="this.closest('dialog').close()"
            class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
            Cancel
        </button>
        <button type="submit"
            class="rounded-xl bg-danger px-4 py-2 text-sm font-semibold text-white transition hover:bg-danger/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger">
            Delete tenant
        </button>
    </form>
</dialog>

<script>
    const tenantDestroyUrlTemplate = @js(route('admin.tenant.destroy', ['tenant' => '__ID__']));

    document.querySelectorAll('[data-tenant-delete]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById('delete-tenant-form').action =
                tenantDestroyUrlTemplate.replace('__ID__', button.dataset.tenantId);
            document.getElementById('delete-tenant-name').textContent = button.dataset.tenantName;
            document.getElementById('delete-tenant-modal').showModal();
        });
    });
</script>
