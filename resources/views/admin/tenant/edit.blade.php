<dialog id="edit-tenant-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 28rem; max-height: 90vh; overflow-y: auto;"
    aria-labelledby="edit-tenant-heading"
    class="rounded-2xl bg-paper3 p-6 shadow-xl backdrop:bg-ink/50">
    <h2 id="edit-tenant-heading" class="font-display text-xl font-bold text-ink">Edit tenant</h2>
    <p class="mt-1 text-sm text-inkSoft">Update this tenant's name and email address.</p>

    @if ($errors->editTenant->any())
        <div role="alert" class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-sm text-danger">
            @foreach ($errors->editTenant->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form id="edit-tenant-form" method="POST" action="" class="mt-4 flex flex-col gap-4">
        @csrf
        @method('PUT')
        <input type="hidden" id="edit-tenant-id" name="editing_id">

        <label for="edit-tenant-name" class="text-sm font-semibold text-ink">Full name
            <input id="edit-tenant-name" name="name" type="text" required maxlength="255" autocomplete="name"
                class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal text-ink focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/15">
        </label>

        <label for="edit-tenant-email" class="text-sm font-semibold text-ink">Email address
            <input id="edit-tenant-email" name="email" type="email" required maxlength="255" autocomplete="email"
                class="mt-1 h-11 w-full rounded-xl border border-ink/10 bg-paper px-3 text-sm font-normal text-ink focus:border-teal focus:outline-none focus:ring-2 focus:ring-teal/15">
        </label>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="this.closest('dialog').close()"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-inkSoft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                Cancel
            </button>
            <button type="submit"
                class="rounded-xl bg-teal px-4 py-2 text-sm font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                Save changes
            </button>
        </div>
    </form>
</dialog>

<script>
    const tenantUpdateUrlTemplate = @js(route('admin.tenant.update', ['tenant' => '__ID__']));

    function openEditTenant(tenant) {
        document.getElementById('edit-tenant-form').action = tenantUpdateUrlTemplate.replace('__ID__', tenant.id);
        document.getElementById('edit-tenant-id').value = tenant.id ?? '';
        document.getElementById('edit-tenant-name').value = tenant.name ?? '';
        document.getElementById('edit-tenant-email').value = tenant.email ?? '';
        document.getElementById('edit-tenant-modal').showModal();
    }

    document.querySelectorAll('[data-tenant-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            openEditTenant({
                id: button.dataset.tenantId,
                name: button.dataset.tenantName,
                email: button.dataset.tenantEmail,
            });
        });
    });

    @if ($errors->editTenant->any())
        openEditTenant({
            id: @js(old('editing_id')),
            name: @js(old('name')),
            email: @js(old('email')),
        });
    @endif
</script>
