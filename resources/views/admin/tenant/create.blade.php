<dialog id="add-tenant-modal"
    style="position: fixed; inset: 50% auto auto 50%; margin: 0; transform: translate(-50%, -50%); width: calc(100% - 2rem); max-width: 32rem; max-height: 90vh; overflow-y: auto;"
    aria-labelledby="add-tenant-heading"
    aria-describedby="add-tenant-description"
    class="rounded-2xl bg-paper3 p-0 text-ink shadow-2xl backdrop:bg-ink/50">
    <div class="border-b border-paper2 bg-paper px-6 py-5 sm:px-7">
        <div class="flex items-start gap-4">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-teal/10 text-teal">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="size-6">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m12-13.5a4 4 0 0 1 0 7.75M20 21v-2a4 4 0 0 0-3-3.87M14 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-teal">Tenant accounts</p>
                <h2 id="add-tenant-heading" class="mt-1 font-display text-xl font-bold">Add a tenant</h2>
                <p id="add-tenant-description" class="mt-1 text-sm leading-5 text-inkSoft">
                    Create an account and send a secure link for the tenant to set their password.
                </p>
            </div>
            <button type="button" aria-label="Close dialog" onclick="this.closest('dialog').close()"
                class="rounded-lg p-2 text-inkSoft transition hover:bg-paper2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="size-5">
                    <path d="m5 5 10 10M15 5 5 15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.tenant.store') }}" class="flex flex-col gap-5 p-6 sm:p-7">
        @csrf

        @if ($errors->addTenant->any())
            <div role="alert" class="flex gap-3 rounded-xl border border-danger/20 bg-danger/5 p-3.5 text-sm text-danger">
                <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="mt-0.5 size-5 shrink-0">
                    <path d="M10 6.5v4m0 3h.01M8.27 3.85 1.9 15a1.5 1.5 0 0 0 1.3 2.25h13.6A1.5 1.5 0 0 0 18.1 15L11.73 3.85a2 2 0 0 0-3.46 0Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div>
                    <p class="font-semibold">We couldn't add this tenant.</p>
                    <p class="mt-1">Review the highlighted fields and try again.</p>
                </div>
            </div>
        @endif

        <label for="add-tenant-name" class="text-sm font-semibold text-ink">
            Full name <span class="text-danger">*</span>
            <input id="add-tenant-name" name="name" type="text" value="{{ old('name') }}"
                required maxlength="255" autocomplete="name" placeholder="e.g. Alex Dela Cruz"
                @if ($errors->addTenant->has('name')) aria-invalid="true" aria-describedby="add-tenant-name-error" @endif
                class="mt-1.5 h-12 w-full rounded-xl border bg-paper px-3.5 text-sm font-normal text-ink placeholder:text-inkSoft/60 focus:outline-none focus:ring-2 {{ $errors->addTenant->has('name') ? 'border-danger/50 focus:border-danger focus:ring-danger/15' : 'border-ink/10 focus:border-teal focus:ring-teal/15' }}">
            @if ($errors->addTenant->has('name'))
                <span id="add-tenant-name-error" class="mt-1.5 block text-xs font-medium text-danger">
                    {{ $errors->addTenant->first('name') }}
                </span>
            @endif
        </label>

        <label for="add-tenant-email" class="text-sm font-semibold text-ink">
            Email address <span class="text-danger">*</span>
            <input id="add-tenant-email" name="email" type="email" value="{{ old('email') }}"
                required maxlength="255" autocomplete="email" placeholder="name@example.com"
                @if ($errors->addTenant->has('email')) aria-invalid="true" aria-describedby="add-tenant-email-error" @endif
                class="mt-1.5 h-12 w-full rounded-xl border bg-paper px-3.5 text-sm font-normal text-ink placeholder:text-inkSoft/60 focus:outline-none focus:ring-2 {{ $errors->addTenant->has('email') ? 'border-danger/50 focus:border-danger focus:ring-danger/15' : 'border-ink/10 focus:border-teal focus:ring-teal/15' }}">
            @if ($errors->addTenant->has('email'))
                <span id="add-tenant-email-error" class="mt-1.5 block text-xs font-medium text-danger">
                    {{ $errors->addTenant->first('email') }}
                </span>
            @endif
        </label>

        <div class="flex gap-3 rounded-xl border border-teal/15 bg-teal/5 p-3.5">
            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="mt-0.5 size-5 shrink-0 text-teal">
                <path d="M10 2.5 3.5 5v4.7c0 4.1 2.8 6.7 6.5 7.8 3.7-1.1 6.5-3.7 6.5-7.8V5L10 2.5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="m7.5 10 1.7 1.7 3.5-3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p class="text-xs leading-5 text-inkSoft">
                The tenant will receive a private setup link by email. An email already used by an account cannot be added again.
            </p>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-paper2 pt-4 sm:flex-row sm:justify-end">
            <button type="button" onclick="this.closest('dialog').close()"
                class="rounded-xl px-4 py-2.5 text-sm font-semibold text-inkSoft transition hover:bg-paper2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                Cancel
            </button>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                Add tenant &amp; send invite
            </button>
        </div>
    </form>
</dialog>

<script>
    const addTenantButton = document.querySelector('[data-tenant-create]');
    if (addTenantButton) {
        addTenantButton.addEventListener('click', () => {
            document.getElementById('add-tenant-modal').showModal();
        });
    }

    @if ($errors->addTenant->any())
        document.getElementById('add-tenant-modal').showModal();
    @endif
</script>
