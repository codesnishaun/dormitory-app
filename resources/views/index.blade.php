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
                    Welcome to Cozy Haven. Find your room, stay on top of bills, and get help with the little things that make a place feel like home.
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
                        <dt class="font-display text-2xl font-semibold text-paper sm:text-3xl">8</dt>
                        <dd class="mt-1 text-[10px] font-medium uppercase tracking-[0.13em] text-paper/50 sm:text-xs">Rooms</dd>
                    </div>
                    <div class="px-4">
                        <dt class="font-display text-2xl font-semibold text-paper sm:text-3xl">7</dt>
                        <dd class="mt-1 text-[10px] font-medium uppercase tracking-[0.13em] text-paper/50 sm:text-xs">Tenants</dd>
                    </div>
                    <div class="pl-4">
                        <dt class="font-display text-2xl font-semibold text-copper sm:text-3xl">2</dt>
                        <dd class="mt-1 text-[10px] font-medium uppercase tracking-[0.13em] text-paper/50 sm:text-xs">Open rooms</dd>
                    </div>
                </dl>
            </div>

            <div class="relative mx-auto w-full max-w-3xl lg:ml-auto">
                <div class="absolute -right-4 -top-5 h-24 w-24 rounded-full bg-copper/20 blur-2xl sm:-right-6 sm:-top-7"></div>
                <div class="absolute -bottom-5 -left-4 h-28 w-28 rounded-full bg-teal/30 blur-2xl sm:-bottom-7 sm:-left-6"></div>

                {{-- Map Card --}}
                <div class="relative rounded-[1.75rem] border border-paper/15 bg-paper3 p-3 shadow-2xl shadow-black/20 sm:p-4">
                    <div
                        id="location"
                        class="relative h-[20rem] w-full overflow-hidden rounded-[1.25rem] bg-paper2 sm:h-[22rem"
                        aria-label="Illustrated neighborhood map showing Cozy Haven in San Ildefonso, Bulacan"
                    > 
                        <span class="absolute left-5 top-5 rounded-full border border-white/70 bg-paper3/90 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-inkSoft backdrop-blur sm:left-7 sm:top-7">
                            Location preview
                        </span>
                        <span class="absolute bottom-5 right-5 rounded-full bg-paper3/90 px-3 py-1.5 text-[11px] font-medium text-inkSoft backdrop-blur sm:bottom-7 sm:right-7">
                            San Ildefonso, Bulacan
                        </span>
                    </div>

                    <div class="flex flex-col gap-4 px-2 pb-1 pt-4 sm:flex-row sm:items-center sm:justify-between sm:px-3 sm:pt-5">
                        <div>
                            <p class="font-display text-lg font-semibold text-ink">Room to settle in.</p>
                            <p class="mt-1 text-sm text-inkSoft">A welcoming place, with the essentials close at hand.</p>
                        </div>
                        <a href="#features" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-tealDeep hover:text-copperDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">
                            More Information
                            <span aria-hidden="true">↓</span>
                        </a>
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
                    The Cozy Haven experience
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
                    <p class="mt-1.5 text-sm leading-relaxed text-paper/65">Take the first step toward making Cozy Haven home.</p>
                </div>
                <x-button :href="route('application_form')" class="w-full shrink-0 sm:w-auto">
                    Start your application
                    <span aria-hidden="true">→</span>
                </x-button>
            </div>
        </div>
    </section>
</x-layout>