<x-layout bodyClass="flex min-h-screen flex-col bg-ink font-sans text-ink">
    <x-slot:title>Set up your tenant account - DORA</x-slot:title>

    <section aria-labelledby="tenant-setup-title" class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6">
        <section class="w-full max-w-lg overflow-hidden rounded-3xl border border-paper/60 bg-paper3 shadow-2xl">
            <div class="bg-ink px-6 py-7 text-center sm:px-10">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
                    <span class="h-8 w-8 rounded-full bg-ink"></span>
                </div>
                <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-paper/60">DORA · B&amp;A Dormitory</p>
                <h1 id="tenant-setup-title" class="mt-2 font-display text-2xl font-bold text-paper sm:text-3xl">Set up your account</h1>
            </div>

            <div class="p-6 sm:p-10">
                <p class="text-sm leading-6 text-inkSoft">
                    Create a secure password to finish setting up your tenant account. Once complete, you can sign in to your DORA dashboard.
                </p>

                @if ($errors->any())
                    <div role="alert" class="mt-5 rounded-xl border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('tenant.setup.store', ['token' => $token]) }}" class="mt-6 flex flex-col gap-5">
                    @csrf

                    <x-form-input
                        name="password"
                        label="Create password"
                        type="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                        aria-describedby="password-guidance"
                    />

                    <p id="password-guidance" class="-mt-3 text-xs leading-5 text-inkSoft">
                        Use at least 12 characters. A longer passphrase is easier to remember and harder to guess.
                    </p>

                    <x-form-input
                        name="password_confirmation"
                        label="Confirm password"
                        type="password"
                        required
                        minlength="12"
                        autocomplete="new-password"
                    />

                    <button type="submit"
                        class="mt-1 inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-teal px-5 py-3 text-sm font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                        Set password
                    </button>
                </form>

                <p class="mt-6 border-t border-paper2 pt-5 text-center text-xs leading-5 text-inkSoft">
                    This setup link can only be used once and expires after 60 minutes. If it has expired, contact your dormitory administrator for a new link.
                </p>
            </div>
        </section>
    </section>
</x-layout>
