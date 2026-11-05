<x-app navigation="none" body-class="min-h-dvh bg-paper3 font-sans text-ink">
    <x-slot:title>Sign in | DORA</x-slot:title>

    <main class="grid min-h-dvh lg:grid-cols-[minmax(0,0.96fr)_minmax(0,1.04fr)]">
        <section
            aria-labelledby="brand-heading"
            class="relative isolate flex min-h-[15rem] flex-col overflow-hidden bg-ink px-6 py-6 text-paper sm:min-h-[17rem] sm:px-10 sm:py-8 lg:min-h-dvh lg:px-[clamp(2rem,6vw,6rem)] lg:py-12"
        >
            <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 right-0 hidden w-[54%] border-l border-paper/[0.06] lg:block"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-20 top-16 hidden h-72 w-72 rounded-full border border-paper/[0.06] lg:block"></div>

            <a href="{{ route('home') }}" aria-label="DORA home" class="relative z-10 inline-flex w-fit items-center gap-3 rounded-lg focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-copper">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl border border-paper/15 bg-paper/[0.06] text-copper shadow-lg shadow-black/10">
                    <svg aria-hidden="true" viewBox="0 0 40 40" class="h-7 w-7 fill-none stroke-current" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M7 17.5 20 7l13 10.5v15H7z" />
                        <path d="M15 32.5V22h10v10.5M12 19h.01M28 19h.01" />
                    </svg>
                </span>
                <span>
                    <span class="block font-display text-2xl font-semibold leading-none tracking-tight text-paper">DORA</span>
                    <span class="mt-1 block text-[10px] font-medium uppercase tracking-[0.18em] text-paper/55">Dormitory Online Response Assistant</span>
                </span>
            </a>

            <div class="relative z-10 mt-8 max-w-lg lg:my-auto lg:py-12">
                <p class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-paper/75">
                    <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-copper"></span>
                    {{ config('dormitory.name') }}
                </p>
                <h1 id="brand-heading" class="mt-3 max-w-[13ch] font-display text-3xl font-medium leading-[1.08] tracking-tight text-paper sm:text-4xl lg:text-6xl">
                    A better way to <span class="text-copper">feel at home.</span>
                </h1>
                <p class="mt-5 hidden max-w-[38ch] text-base leading-relaxed text-paper/65 lg:block">
                    Keep the everyday details of dorm living in one calm, connected place.
                </p>
            </div>

            <svg
                aria-hidden="true"
                viewBox="0 0 520 390"
                class="pointer-events-none absolute bottom-0 right-0 hidden h-[49%] w-[56%] max-w-[34rem] lg:block"
                fill="none"
            >
                <path d="M18 367h500" stroke="#F6EEDD" stroke-opacity=".18" />
                <path d="M80 367V175l120-78 120 78v192" stroke="#F6EEDD" stroke-opacity=".55" stroke-width="2" />
                <path d="M80 175h240M104 367V194h192v173" stroke="#F6EEDD" stroke-opacity=".22" />
                <path d="M132 367V235h51v-41h37v41h52v132" stroke="#F6EEDD" stroke-opacity=".42" stroke-width="2" />
                <path d="M146 254h20m-20 27h20m-20 27h20m-20 27h20m42-81h20m-20 27h20m-20 27h20m-20 27h20" stroke="#C1712F" stroke-opacity=".9" stroke-width="3" />
                <path d="M350 367V205l77-51 77 51v162" stroke="#F6EEDD" stroke-opacity=".32" stroke-width="2" />
                <path d="M377 232h21v29h-21zm49 0h21v29h-21zm-49 57h21v29h-21zm49 0h21v29h-21z" stroke="#2F6F6E" stroke-width="2" />
                <path d="M418 154v-28h18v40" stroke="#F6EEDD" stroke-opacity=".32" stroke-width="2" />
                <circle cx="430" cy="91" r="29" fill="#C1712F" fill-opacity=".12" />
                <circle cx="430" cy="91" r="18" stroke="#C1712F" stroke-opacity=".65" />
            </svg>

            <p class="relative z-10 hidden text-xs text-paper/60 lg:block">A thoughtful home for the details that matter.</p>
        </section>

        <section aria-labelledby="login-heading" class="flex min-w-0 items-center justify-center bg-paper3 px-5 py-10 sm:px-8 sm:py-12 lg:px-12">
            <div class="w-full max-w-[27rem]">
                <div class="mb-8">
                    <p class="mb-3 inline-flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-teal">
                        <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-copper"></span>
                        Secure account access
                    </p>
                    <h2 id="login-heading" class="font-display text-3xl font-semibold tracking-tight text-ink sm:text-4xl">Welcome back</h2>
                    <p class="mt-2 text-sm leading-relaxed text-inkSoft">Sign in to your {{ config('dormitory.name') }} account and pick up where you left off.</p>
                </div>

                @if (session('status'))
                    <div role="status" class="mb-6 rounded-xl border border-teal/20 bg-teal/10 px-4 py-3 text-sm text-tealDeep">
                        {{ session('status') }}
                    </div>
                @endif

                <form id="login-form" method="POST" action="{{ route('login.store') }}" class="space-y-6" data-login-form>
                    @csrf

                    <fieldset class="space-y-3">
                        <legend class="text-sm font-semibold text-ink">I’m signing in as</legend>
                        <div class="grid grid-cols-2 gap-3" role="radiogroup" aria-label="Choose account type">
                            <label class="cursor-pointer">
                                <input
                                    class="peer sr-only"
                                    type="radio"
                                    name="login_role"
                                    value="admin"
                                    required
                                    @checked(old('login_role', 'admin') === 'admin')
                                    @if ($errors->has('login_role')) aria-invalid="true" aria-describedby="role-error" @endif
                                >
                                <span class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-ink/10 bg-paper3/70 px-3 text-sm font-semibold text-inkSoft transition-colors hover:border-ink/25 peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:outline-solid peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-copper motion-reduce:transition-none">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M3 17h14M5 17V7l5-4 5 4v10M8 17v-5h4v5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Admin
                                </span>
                            </label>
                            <label class="cursor-pointer">
                                <input
                                    class="peer sr-only"
                                    type="radio"
                                    name="login_role"
                                    value="tenant"
                                    required
                                    @checked(old('login_role', 'admin') === 'tenant')
                                    @if ($errors->has('login_role')) aria-invalid="true" aria-describedby="role-error" @endif
                                >
                                <span class="flex min-h-12 items-center justify-center gap-2 rounded-xl border border-ink/10 bg-paper3/70 px-3 text-sm font-semibold text-inkSoft transition-colors hover:border-ink/25 peer-checked:border-ink peer-checked:bg-ink peer-checked:text-paper peer-focus-visible:outline-solid peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-copper motion-reduce:transition-none">
                                    <svg aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M3 17h14M5 17V7l5-4 5 4v10M8 10h.01M12 10h.01" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Tenant
                                </span>
                            </label>
                        </div>
                        @error('login_role')
                            <p id="role-error" role="alert" class="text-xs font-medium text-danger">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div class="space-y-5">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-ink">Email address</label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                autocomplete="username"
                                inputmode="email"
                                required
                                @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @else aria-invalid="false" @endif
                                class="w-full rounded-xl border border-ink/15 bg-paper3 px-4 py-3 text-sm text-ink shadow-sm shadow-ink/[0.02] outline-none transition placeholder:text-inkSoft/45 hover:border-ink/25 focus:border-teal focus:ring-4 focus:ring-teal/10 focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal @error('email') border-danger focus:border-danger focus:ring-danger/10 @enderror"
                                placeholder="you@example.com"
                            >
                            @error('email')
                                <p id="email-error" role="alert" class="mt-2 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-ink">Password</label>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @else aria-invalid="false" @endif
                                    class="w-full rounded-xl border border-ink/15 bg-paper3 py-3 pl-4 pr-20 text-sm text-ink shadow-sm shadow-ink/[0.02] outline-none transition placeholder:text-inkSoft/45 hover:border-ink/25 focus:border-teal focus:ring-4 focus:ring-teal/10 focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal @error('password') border-danger focus:border-danger focus:ring-danger/10 @enderror"
                                    placeholder="Enter your password"
                                >
                                <button
                                    type="button"
                                    hidden
                                    data-password-toggle
                                    aria-controls="password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                    class="absolute inset-y-1 right-1 inline-flex items-center gap-1.5 rounded-lg px-3 text-xs font-semibold text-tealDeep transition hover:bg-teal/10 focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal motion-reduce:transition-none"
                                >
                                    <svg data-eye-icon aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="M2.5 10s2.7-5 7.5-5 7.5 5 7.5 5-2.7 5-7.5 5-7.5-5-7.5-5Z" />
                                        <circle cx="10" cy="10" r="2" />
                                    </svg>
                                    <svg data-eye-off-icon aria-hidden="true" viewBox="0 0 20 20" class="hidden h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6">
                                        <path d="m3 3 14 14M8.6 5.2A8 8 0 0 1 10 5c4.8 0 7.5 5 7.5 5a12 12 0 0 1-2.1 2.7M5.2 5.8C3.4 7 2.5 10 2.5 10s2.7 5 7.5 5c.6 0 1.2-.1 1.8-.3" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span data-toggle-label>Show</span>
                                </button>
                            </div>
                            @error('password')
                                <p id="password-error" role="alert" class="mt-2 text-xs font-medium text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <button
                        id="login-submit"
                        type="submit"
                        class="group inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-copperDeep px-5 py-3 text-sm font-semibold text-paper shadow-lg shadow-copper/15 transition hover:-translate-y-px hover:bg-ink focus-visible:outline-solid focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper active:translate-y-0 disabled:cursor-wait disabled:opacity-70 motion-reduce:transition-none"
                    >
                        <span data-submit-label>Sign in</span>
                        <svg data-loading-indicator aria-hidden="true" viewBox="0 0 20 20" class="hidden h-4 w-4 motion-reduce:animate-none" fill="none">
                            <circle cx="10" cy="10" r="7" stroke="currentColor" stroke-opacity=".35" stroke-width="2.5" />
                            <path d="M17 10a7 7 0 0 0-7-7" stroke="currentColor" stroke-linecap="round" stroke-width="2.5" class="animate-spin motion-reduce:animate-none" />
                        </svg>
                        <svg data-submit-arrow aria-hidden="true" viewBox="0 0 20 20" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path d="M3.5 10h13m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </form>

                <p class="mt-6 border-t border-ink/10 pt-5 text-center text-xs leading-relaxed text-inkSoft/75">
                    Use the email address linked to your {{ config('dormitory.name') }} account.
                </p>
            </div>
        </section>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.querySelector('[data-password-toggle]');

        if (passwordInput && passwordToggle) {
            const toggleLabel = passwordToggle.querySelector('[data-toggle-label]');
            const eyeIcon = passwordToggle.querySelector('[data-eye-icon]');
            const eyeOffIcon = passwordToggle.querySelector('[data-eye-off-icon]');

            passwordToggle.hidden = false;
            passwordToggle.addEventListener('click', () => {
                const isVisible = passwordInput.type === 'password';

                passwordInput.type = isVisible ? 'text' : 'password';
                passwordToggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
                passwordToggle.setAttribute('aria-pressed', String(isVisible));
                toggleLabel.textContent = isVisible ? 'Hide' : 'Show';
                eyeIcon.classList.toggle('hidden', isVisible);
                eyeOffIcon.classList.toggle('hidden', !isVisible);
            });
        }

        const loginForm = document.querySelector('[data-login-form]');

        if (loginForm) {
            loginForm.addEventListener('submit', () => {
                const submitButton = document.getElementById('login-submit');
                const submitLabel = submitButton.querySelector('[data-submit-label]');
                const loadingIndicator = submitButton.querySelector('[data-loading-indicator]');
                const submitArrow = submitButton.querySelector('[data-submit-arrow]');

                submitButton.disabled = true;
                loginForm.setAttribute('aria-busy', 'true');
                submitLabel.textContent = 'Signing in…';
                loadingIndicator.classList.remove('hidden');
                submitArrow.classList.add('hidden');
            });
        }
    </script>
</x-app>
