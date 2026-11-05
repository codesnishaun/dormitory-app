<x-layout bodyClass="min-h-screen flex flex-col bg-ink font-sans">
    <x-slot:title>
        B&A Dormitory | DORA
    </x-slot:title>

    <section class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_12%_18%,rgba(193,113,47,0.2),transparent_38%),radial-gradient(ellipse_at_88%_75%,rgba(47,111,110,0.18),transparent_42%)]"></div>

        <div class="mx-auto grid w-full max-w-7xl items-center gap-12 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:px-12 lg:py-24">
            <div>
                <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-paper/15 bg-paper/[0.05] px-3.5 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-paper/75">
                    <span class="h-1.5 w-1.5 rounded-full bg-copper"></span>
                    San Ildefonso, Bulacan
                </div>

                <h1 class="max-w-[15ch] font-display text-[clamp(2.75rem,6vw,5.25rem)] font-medium leading-[0.99] tracking-tight text-paper">
                    A better way to <em class="text-copper">feel at home.</em>
                </h1>

                <p class="mt-6 max-w-[48ch] text-base leading-relaxed text-paper/70 sm:text-lg">
                    Welcome to Bryan & Aaron Dormitory. Find your room, stay on top of bills, and get help with the little things that make a place feel like home.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <x-button :href="route('application_form')" class="w-full sm:w-auto">
                        Apply for a room
                        <span aria-hidden="true">→</span>
                    </x-button>
                    <x-button variant="secondary" :href="route('login')" class="w-full sm:w-auto">
                        Log in to your account
                    </x-button>
                </div>

                <dl class="mt-10 grid max-w-md grid-cols-3 divide-x divide-paper/15 border-y border-paper/15 py-4">
                    <div class="pr-4">
                        <dt class="font-display text-2xl font-semibold text-paper sm:text-3xl">{{ $totalRooms }}</dt>
                        <dd class="mt-1 text-[10px] font-medium uppercase tracking-[0.13em] text-paper/50 sm:text-xs">Rooms</dd>
                    </div>
                    <div class="px-4">
                        <dt class="font-display text-2xl font-semibold text-paper sm:text-3xl">{{ $tenantCount }}</dt>
                        <dd class="mt-1 text-[10px] font-medium uppercase tracking-[0.13em] text-paper/50 sm:text-xs">Tenants</dd>
                    </div>
                    <div class="pl-4">
                        <dt class="font-display text-2xl font-semibold text-copper sm:text-3xl">{{ $openRooms }}</dt>
                        <dd class="mt-1 text-[10px] font-medium uppercase tracking-[0.13em] text-paper/50 sm:text-xs">Open rooms</dd>
                    </div>
                </dl>
            </div>

            <div class="relative mx-auto w-full max-w-3xl lg:ml-auto">
                <div class="absolute -right-4 -top-5 h-24 w-24 rounded-full bg-copper/20 blur-2xl sm:-right-6 sm:-top-7"></div>
                <div class="absolute -bottom-5 -left-4 h-28 w-28 rounded-full bg-teal/30 blur-2xl sm:-bottom-7 sm:-left-6"></div>

                <div class="relative rounded-[1.75rem] border border-paper/15 bg-paper3 p-3 shadow-2xl shadow-black/20 sm:p-4">
                    <div
                        id="location"
                        data-dormitory-location
                        data-latitude="{{ $dormitoryLocation['latitude'] ?? '' }}"
                        data-longitude="{{ $dormitoryLocation['longitude'] ?? '' }}"
                        data-name="{{ $dormitoryLocation['name'] }}"
                        data-address="{{ $dormitoryLocation['address'] }}"
                        class="relative h-[20rem] w-full overflow-hidden rounded-[1.25rem] bg-paper2 sm:h-[24rem] lg:h-[27rem]"
                    >
                        <div
                            id="dormitory-map"
                            class="absolute inset-0 z-0"
                            role="region"
                            aria-label="Interactive map showing {{ $dormitoryLocation['name'] }} in {{ $dormitoryLocation['address'] }}"
                        ></div>

                        <div
                            id="map-fallback"
                            class="absolute inset-0 z-[500] flex flex-col items-center justify-center gap-3 bg-paper2 px-6 text-center"
                            role="status"
                            aria-live="polite"
                        >
                            <span class="grid h-12 w-12 place-items-center rounded-full bg-ink text-2xl text-copper" aria-hidden="true">⌂</span>
                            @if ($dormitoryCoordinatesConfigured)
                                <span class="max-w-sm text-sm font-medium leading-relaxed text-inkSoft">
                                    Loading the interactive location map. If it does not appear, use the Google Maps option below.
                                </span>
                            @else
                                <span class="max-w-sm text-sm font-medium leading-relaxed text-inkSoft">
                                    The interactive map is waiting for verified dormitory coordinates. No approximate marker is shown.
                                </span>
                            @endif
                        </div>
                    </div>
                    <p id="map-status" class="px-2 pt-2 text-xs text-inkSoft" aria-live="polite"></p>

                    <div class="flex flex-col gap-4 px-2 pb-1 pt-4 sm:px-3 sm:pt-5">
                        <div>
                            <p class="font-display text-lg font-semibold text-ink">{{ $dormitoryLocation['name'] }}</p>
                            <p class="mt-1 text-sm text-inkSoft">{{ $dormitoryLocation['address'] }}</p>
                        </div>
                        <div class="flex flex-col gap-3 border-t border-paper2 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="max-w-sm">
                                <p class="text-sm leading-relaxed text-inkSoft">
                                    Your location is requested only when you choose Get Directions. It is used to open Google Maps and is never stored or sent to DORA.
                                </p>
                                @unless ($dormitoryCoordinatesConfigured)
                                    <p class="mt-2 text-xs font-medium leading-relaxed text-copperDeep">
                                        Automatic directions are unavailable until the dormitory coordinates are verified. Confirm the destination before starting a manual route.
                                    </p>
                                @endunless
                            </div>
                            <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                                <button
                                    id="get-directions"
                                    type="button"
                                    class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-copper px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-copperDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal disabled:cursor-wait disabled:opacity-70"
                                    aria-describedby="directions-status"
                                >
                                    <svg data-loading-indicator class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    <span data-directions-label>Get Directions</span>
                                </button>
                                <a
                                    href="https://www.google.com/maps/dir/?api=1&amp;destination={{ urlencode($dormitoryLocation['name'].', '.$dormitoryLocation['address']) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-tealDeep transition hover:bg-teal/10 hover:text-copperDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                                >
                                    Open manual directions in Google Maps
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </div>
                        <p id="directions-status" class="min-h-5 text-sm font-medium text-inkSoft" role="status" aria-live="polite">
                            @unless ($dormitoryCoordinatesConfigured)
                                Dormitory coordinates have not been configured yet.
                            @endunless
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="scroll-mt-24 bg-paper px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 max-w-2xl sm:mb-12">
                <div class="mb-3 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-copperDeep">
                    <span class="h-1.5 w-1.5 rounded-full bg-copper"></span>
                    The Bryan & Aaron Dormitory experience
                </div>
                <h2 class="font-display text-[clamp(1.9rem,3.5vw,3rem)] font-medium leading-tight tracking-tight text-ink">
                    The details of home, <em class="text-teal">all in one place.</em>
                </h2>
                <p class="mt-4 max-w-xl text-[15px] leading-relaxed text-inkSoft sm:text-base">
                    Less back-and-forth, more peace of mind. DORA brings the everyday essentials of dorm living together for residents and management.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <x-card
                    icon="👥"
                    title="Tenant care"
                    description="Keep tenant profiles, contact details, move-in dates, and balances easy to find."
                    class="h-full"
                />
                <x-card
                    icon="⌂"
                    title="Rooms & utilities"
                    description="Keep room details and individual electricity readings organized in one place."
                    iconColor="text-ink"
                    class="h-full"
                />
                <x-card
                    icon="📣"
                    title="House updates"
                    description="Share important notices so residents can stay in the loop."
                    class="h-full"
                />
                <x-card
                    icon="🛠"
                    title="Repairs"
                    description="Follow a maintenance request from the first report through resolution."
                    iconColor="text-danger"
                    class="h-full"
                />
                <x-card
                    icon="✳"
                    title="DORA assistant"
                    description="Get help answering common questions and preparing house notices."
                    iconColor="text-ink"
                    class="h-full"
                />
            </div>

            <div class="mt-10 flex flex-col gap-5 rounded-3xl bg-ink px-6 py-7 text-paper sm:flex-row sm:items-center sm:justify-between sm:px-9 sm:py-8">
                <div>
                    <p class="font-display text-xl font-semibold sm:text-2xl">Ready to find your place?</p>
                    <p class="mt-1.5 text-sm leading-relaxed text-paper/65">Take the first step toward making Bryan & Aaron Dormitory home.</p>
                </div>
                <x-button :href="route('application_form')" class="w-full shrink-0 sm:w-auto">
                    Start your application
                    <span aria-hidden="true">→</span>
                </x-button>
            </div>
        </div>
    </section>
    @push('scripts')
        @vite('resources/js/app.js')
    @endpush
</x-layout>