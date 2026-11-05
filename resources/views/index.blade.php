<x-layout bodyClass="min-h-screen flex flex-col bg-tealDeep font-sans text-ink ">
    <x-slot:title>{{ $dormitoryLocation['name'] }} | DORA</x-slot:title>

    <section aria-labelledby="hero-heading" class="overflow-hidden bg-paper3">
        <div class="mx-auto grid w-full max-w-7xl items-center gap-12 px-5 py-14 sm:px-8 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16 lg:px-12 lg:py-24">
            <div>
                <div class="mb-5 flex flex-col gap-1 text-xs font-semibold uppercase tracking-[0.16em] text-tealDeep">
                    <p class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-copper" aria-hidden="true"></span>
                        {{ $dormitoryLocation['name'] }}
                    </p>
                    <p class="pl-4 font-medium tracking-[0.1em] text-inkSoft">{{ $dormitoryLocation['address'] }}</p>
                </div>

                <h1 id="hero-heading" class="max-w-[13ch] font-display text-[clamp(2.9rem,6.2vw,5.5rem)] font-medium leading-[0.98] tracking-tight text-ink">
                    Room to <em class="text-copperDeep">feel at home.</em>
                </h1>

                <p class="mt-6 max-w-[48ch] text-base leading-relaxed text-inkSoft sm:text-lg">
                    Explore current room details, then send an application through DORA. The dormitory team will review your request and follow up with you.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                    <a
                        href="{{ route('application_form') }}"
                        class="inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-ink px-6 py-3 text-sm font-semibold text-paper3 shadow-sm transition hover:bg-inkSoft active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >
                        Apply now
                        <span aria-hidden="true">→</span>
                    </a>
                    <a
                        href="#rooms"
                        class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-ink/15 px-6 py-3 text-sm font-semibold text-ink transition hover:border-teal hover:bg-teal/5 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >
                        Explore rooms
                    </a>
                </div>

                <dl class="mt-10 grid max-w-md grid-cols-2 divide-x divide-paper2 border-y border-paper2 py-4">
                    <div class="pr-5">
                        <dd class="font-display text-3xl font-semibold text-ink sm:text-4xl">{{ $totalRooms }}</dd>
                        <dt class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-inkSoft">Rooms listed</dt>
                    </div>
                    <div class="pl-5">
                        <dd class="font-display text-3xl font-semibold text-tealDeep sm:text-4xl">{{ $openRooms }}</dd>
                        <dt class="mt-1 text-xs font-medium uppercase tracking-[0.12em] text-inkSoft">Currently available</dt>
                    </div>
                </dl>
            </div>

            <aside aria-label="Dormitory at a glance" class="relative mx-auto w-full max-w-xl lg:ml-auto">
                <div class="rounded-[2rem] border border-paper2 bg-paper p-6 shadow-xl shadow-ink/5 sm:p-9">
                    <div class="flex min-h-[15rem] flex-col justify-between rounded-[1.5rem] bg-ink p-6 text-paper3 sm:min-h-[19rem] sm:p-9">
                        <div class="flex items-start justify-between gap-5">
                            <span class="inline-flex rounded-full border border-paper/20 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-paper/75">
                                A place to settle in
                            </span>
                            <svg class="h-9 w-9 shrink-0 text-copper" viewBox="0 0 40 40" fill="none" aria-hidden="true">
                                <path d="M7 18.5 20 8l13 10.5V33H7V18.5Z" stroke="currentColor" stroke-width="1.8" />
                                <path d="M16 33V22h8v11M12 19h.01M28 19h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-display text-3xl leading-tight sm:text-4xl">{{ $dormitoryLocation['name'] }}</p>
                            <p class="mt-3 max-w-sm text-sm leading-relaxed text-paper/70">{{ $dormitoryLocation['address'] }}</p>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-5">
                        <div>
                            <p class="font-display text-3xl font-semibold text-ink">{{ $openRooms }}</p>
                            <p class="mt-1 text-sm text-inkSoft">Rooms currently available</p>
                        </div>
                        <div class="border-l border-paper2 pl-5">
                            <p class="font-display text-lg font-semibold text-ink">DORA</p>
                            <p class="mt-1 text-sm leading-relaxed text-inkSoft">Applications and resident services in one place.</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section id="about" aria-labelledby="about-heading" class="scroll-mt-24 bg-paper px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
        <div class="mx-auto grid max-w-7xl gap-10 md:grid-cols-[0.75fr_1.25fr] md:gap-16">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-copperDeep">Get to know the dormitory</p>
                <h2 id="about-heading" class="mt-4 max-w-[12ch] font-display text-[clamp(2rem,4vw,3.5rem)] font-medium leading-tight tracking-tight text-ink">
                    A place to <em class="text-tealDeep">settle in.</em>
                </h2>
            </div>
            <div class="flex flex-col justify-center gap-5">
                <p class="max-w-2xl text-base leading-relaxed text-inkSoft sm:text-lg">
                    {{ $dormitoryLocation['name'] }} is located in {{ $dormitoryLocation['address'] }}. DORA helps the team manage room applications and gives residents access to announcements, room billing information, maintenance requests, and common-question support.
                </p>
                <a
                    href="#resident-services"
                    class="inline-flex min-h-11 w-fit items-center gap-2 text-sm font-semibold text-tealDeep transition hover:text-copperDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal"
                >
                    Explore resident services
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </div>
    </section>

    <section id="rooms" aria-labelledby="rooms-heading" class="scroll-mt-24 bg-paper3 px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 flex flex-col gap-5 sm:mb-12 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-copperDeep">Rooms and accommodation</p>
                    <h2 id="rooms-heading" class="mt-3 font-display text-[clamp(2rem,4vw,3.5rem)] font-medium leading-tight tracking-tight text-ink">
                        Room details, <em class="text-tealDeep">made clear.</em>
                    </h2>
                    <p class="mt-3 max-w-xl text-[15px] leading-relaxed text-inkSoft sm:text-base">
                        Review the listed room capacity, monthly rate, and current availability. The application form is the only way to submit a room request.
                    </p>
                </div>
                <p class="text-sm text-inkSoft">{{ $totalRooms }} {{ \Illuminate\Support\Str::plural('room', $totalRooms) }} listed</p>
            </div>

            @if ($rooms->isEmpty())
                <div class="rounded-2xl border border-paper2 bg-paper p-7 sm:p-10">
                    <h3 class="font-display text-xl font-semibold text-ink">Room information is not available yet.</h3>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-inkSoft">
                        You can still submit an application. The dormitory team will review your request and contact you about the next steps.
                    </p>
                    <a
                        href="{{ route('application_form') }}"
                        class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-xl bg-ink px-5 py-2.5 text-sm font-semibold text-paper3 transition hover:bg-inkSoft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >
                        Start an application
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($rooms as $room)
                        <article class="flex h-full flex-col rounded-2xl border border-paper2 bg-paper p-5 transition hover:border-teal/40 hover:shadow-lg hover:shadow-ink/5 sm:p-6">
                            <div class="flex items-start justify-between gap-4 border-b border-paper2 pb-5">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-inkSoft">Room</p>
                                    <h3 class="mt-1 font-display text-2xl font-semibold text-ink">{{ $room['room_number'] }}</h3>
                                </div>
                                <span @class([
                                    'inline-flex min-h-8 items-center rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-teal/10 text-tealDeep' => $room['is_available'],
                                    'bg-ink/5 text-inkSoft' => ! $room['is_available'],
                                ])>
                                    {{ $room['is_available'] ? 'Available' : 'Not available' }}
                                </span>
                            </div>

                            <dl class="grid grid-cols-2 gap-4 py-5">
                                <div>
                                    <dt class="text-xs font-medium text-inkSoft">Room capacity</dt>
                                    <dd class="mt-1 text-sm font-semibold text-ink">{{ $room['capacity'] }} {{ \Illuminate\Support\Str::plural('person', $room['capacity']) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-inkSoft">Monthly rate</dt>
                                    <dd class="mt-1 text-sm font-semibold text-ink">₱{{ number_format((float) $room['monthly_rent'], 2) }}</dd>
                                </div>
                            </dl>

                            <div class="mt-auto border-t border-paper2 pt-4">
                                @if ($room['is_available'])
                                    <a
                                        href="{{ route('application_form') }}"
                                        class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-tealDeep transition hover:text-copperDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal"
                                    >
                                        Apply for a room
                                        <span aria-hidden="true">→</span>
                                    </a>
                                @else
                                    <p class="text-sm font-medium text-inkSoft">Availability may change. Submit an application to be considered.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section id="resident-services" aria-labelledby="services-heading" class="scroll-mt-24 bg-paper px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
        <div class="mx-auto max-w-7xl">
            <div class="mb-9 max-w-2xl sm:mb-12">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-copperDeep">Resident services</p>
                <h2 id="services-heading" class="mt-3 font-display text-[clamp(2rem,4vw,3.5rem)] font-medium leading-tight tracking-tight text-ink">
                    The everyday essentials, <em class="text-tealDeep">in DORA.</em>
                </h2>
                <p class="mt-3 max-w-xl text-[15px] leading-relaxed text-inkSoft sm:text-base">
                    Current residents can use DORA to keep up with key information and requests.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-card
                    icon="⌂"
                    title="Room bills"
                    description="Review room billing information in the resident area."
                    iconColor="text-ink"
                    class="h-full"
                />
                <x-card
                    icon="📣"
                    title="House announcements"
                    description="Read published updates from dormitory management."
                    class="h-full"
                />
                <x-card
                    icon="🛠"
                    title="Maintenance requests"
                    description="Send a maintenance request through the resident area."
                    iconColor="text-tealDeep"
                    class="h-full"
                />
                <x-card
                    icon="✳"
                    title="Ask DORA"
                    description="Get help with common questions and resident information."
                    iconColor="text-ink"
                    class="h-full"
                />
            </div>
        </div>
    </section>

    <section id="location" aria-labelledby="location-heading" class="scroll-mt-24 bg-paper3 px-5 py-16 sm:px-8 sm:py-20 lg:px-12 lg:py-24">
        <div class="mx-auto grid max-w-7xl items-center gap-9 lg:grid-cols-[0.8fr_1.2fr] lg:gap-14">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-copperDeep">Location</p>
                <h2 id="location-heading" class="mt-3 font-display text-[clamp(2rem,4vw,3.5rem)] font-medium leading-tight tracking-tight text-ink">
                    Find the <em class="text-tealDeep">dormitory.</em>
                </h2>
                <p class="mt-4 font-display text-xl font-semibold text-ink">{{ $dormitoryLocation['name'] }}</p>
                <p class="mt-1 text-sm leading-relaxed text-inkSoft">{{ $dormitoryLocation['address'] }}</p>
                <p class="mt-5 max-w-lg text-sm leading-relaxed text-inkSoft">
                    Your location is requested only if you choose Get Directions. It opens a Google Maps route and is not stored or sent to DORA.
                </p>
                @unless ($dormitoryCoordinatesConfigured)
                    <p class="mt-3 max-w-lg text-sm leading-relaxed text-copperDeep">
                        Automatic directions are unavailable until verified dormitory coordinates are configured. You can still open Google Maps and confirm the destination manually.
                    </p>
                @endunless
            </div>

            <div
                id="dormitory-location"
                data-dormitory-location
                data-latitude="{{ $dormitoryLocation['latitude'] ?? '' }}"
                data-longitude="{{ $dormitoryLocation['longitude'] ?? '' }}"
                data-name="{{ $dormitoryLocation['name'] }}"
                data-address="{{ $dormitoryLocation['address'] }}"
                class="rounded-3xl border border-paper2 bg-paper p-3 shadow-lg shadow-ink/5 sm:p-4"
            >
                <div class="relative h-[18rem] overflow-hidden rounded-2xl bg-paper2 sm:h-[24rem] lg:h-[27rem]">
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
                                Loading the interactive location map. If it does not appear, use the manual directions link.
                            </span>
                        @else
                            <span class="max-w-sm text-sm font-medium leading-relaxed text-inkSoft">
                                The interactive map is waiting for verified dormitory coordinates. No approximate location is shown.
                            </span>
                        @endif
                    </div>
                </div>
                <p id="map-status" class="px-2 pt-2 text-xs text-inkSoft" aria-live="polite"></p>

                <div class="flex flex-col gap-4 px-2 pb-1 pt-4 sm:px-3 sm:pt-5">
                    <div class="flex flex-col gap-3 border-t border-paper2 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-2">
                            <button
                                id="get-directions"
                                type="button"
                                class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-ink px-5 py-3 text-sm font-semibold text-paper3 transition hover:bg-inkSoft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal disabled:cursor-wait disabled:opacity-70"
                                aria-describedby="directions-status"
                            >
                                <svg data-loading-indicator class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span data-directions-label>Get Directions</span>
                            </button>
                            <p id="directions-status" class="min-h-5 text-sm font-medium text-inkSoft" role="status" aria-live="polite">
                                @unless ($dormitoryCoordinatesConfigured)
                                    Dormitory coordinates have not been configured yet.
                                @endunless
                            </p>
                        </div>
                        <a
                            href="https://www.google.com/maps/dir/?api=1&amp;destination={{ urlencode($dormitoryLocation['name'].', '.$dormitoryLocation['address']) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold text-tealDeep transition hover:bg-teal/10 hover:text-copperDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                        >
                            Open manual directions in Google Maps
                            <span aria-hidden="true">↗</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="apply" aria-labelledby="apply-heading" class="scroll-mt-24 bg-ink px-5 py-14 text-paper3 sm:px-8 sm:py-16 lg:px-12">
        <div class="mx-auto flex max-w-7xl flex-col gap-7 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-paper/65">Start with an application</p>
                <h2 id="apply-heading" class="mt-3 font-display text-[clamp(1.9rem,3.4vw,2.75rem)] font-medium leading-tight">
                    Ready to take the next step?
                </h2>
                <p class="mt-3 text-sm leading-relaxed text-paper/75 sm:text-base">
                    Share your contact details and preferred move-in date. The team will review your request and follow up; submitting an application does not create a login.
                </p>
            </div>
            <a
                href="{{ route('application_form') }}"
                class="inline-flex min-h-12 shrink-0 items-center justify-center gap-3 rounded-xl bg-paper3 px-6 py-3 text-sm font-semibold text-ink transition hover:bg-paper2 active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-paper3"
            >
                Apply for a room
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    @push('scripts')
        @vite('resources/js/app.js')
    @endpush
</x-layout>
