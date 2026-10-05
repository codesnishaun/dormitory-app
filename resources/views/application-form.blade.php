<x-layout bodyClass="min-h-screen flex flex-col bg-paper font-sans text-ink">
    <x-slot:title>Apply for a Room | DORA</x-slot:title>

    @if (session('applied'))
        <section class="relative isolate flex flex-1 items-center justify-center overflow-hidden px-5 py-12 sm:px-8">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_15%_25%,rgba(193,113,47,0.13),transparent_40%),radial-gradient(ellipse_at_85%_75%,rgba(47,111,110,0.14),transparent_42%)]"></div>

            <div class="w-full max-w-xl rounded-3xl border border-paper2 bg-paper3 p-7 text-center shadow-xl sm:p-10">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-success/15 text-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-7 w-7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                    </svg>
                </span>
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.14em] text-success">Application submitted</p>
                <h1 class="mt-2 font-display text-3xl font-semibold text-ink">Thanks for reaching out.</h1>
                <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-inkSoft">
                    The B&amp;A Dormitory team will review your details and contact you using the information you provided.
                </p>
                <x-button variant="copper" :href="route('home')" class="mt-7">Back to home</x-button>
            </div>
        </section>
    @else
        <section class="relative isolate flex-1 overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_12%_10%,rgba(193,113,47,0.12),transparent_34%),radial-gradient(ellipse_at_90%_75%,rgba(47,111,110,0.12),transparent_38%)]"></div>

            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12 lg:px-12 lg:py-16">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-inkSoft transition hover:text-copperDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal"
                >
                    <span aria-hidden="true">←</span>
                    Back to home
                </a>

                <div class="mt-7 grid items-start gap-8 lg:mt-10 lg:grid-cols-2 lg:gap-14">
                    <div class="lg:sticky lg:top-28">
                        <span class="inline-flex items-center gap-2 border rounded-full bg-copper/10 mt-5 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-copperDeep">
                            <span class="h-1.5 w-1.5 rounded-full bg-copper"></span>
                            Room application
                        </span>

                        <h1 class="mt-5 max-w-[12ch] font-display text-[clamp(2.5rem,5vw,4rem)] font-medium leading-[1.02] tracking-tight text-ink">
                            Make room for <em class="text-copper">what’s next.</em>
                        </h1>
                        <p class="mt-5 max-w-lg text-base leading-relaxed text-inkSoft">
                            Tell us a little about yourself and when you’d like to move in. The B&amp;A Dormitory team will follow up with you about your application.
                        </p>

                        <div class="mt-8 rounded-2xl border border-paper2 bg-paper3 p-5 sm:p-6">
                            <h2 class="font-display text-lg font-bold">What happens next</h2>
                            <ol class="mt-5 flex flex-col gap-5">
                                <li class="flex gap-3.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-copper/15 text-sm font-bold text-copperDeep">1</span>
                                    <div>
                                        <h3 class="text-sm font-semibold">Send your details</h3>
                                        <p class="mt-0.5 text-xs leading-relaxed text-inkSoft">Share your contact information and room preference.</p>
                                    </div>
                                </li>
                                <li class="flex gap-3.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal/15 text-sm font-bold text-tealDeep">2</span>
                                    <div>
                                        <h3 class="text-sm font-semibold">We review your request</h3>
                                        <p class="mt-0.5 text-xs leading-relaxed text-inkSoft">Our team checks room availability and your preferred date.</p>
                                    </div>
                                </li>
                                <li class="flex gap-3.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-paper2 text-sm font-bold text-inkSoft">3</span>
                                    <div>
                                        <h3 class="text-sm font-semibold">We’ll be in touch</h3>
                                        <p class="mt-0.5 text-xs leading-relaxed text-inkSoft">We’ll contact you using the details you provide.</p>
                                    </div>
                                </li>
                            </ol>
                        </div>

                        <p class="mt-4 text-xs leading-relaxed text-inkSoft">
                            <span class="font-semibold text-ink">B&amp;A Dormitory</span>
                            <span aria-hidden="true"> · </span>
                            San Ildefonso, Bulacan
                        </p>
                    </div>

                    <div class="rounded-3xl border border-paper2 bg-paper3 p-5 shadow-xl shadow-ink/5 sm:p-8 lg:p-9">
                        <div class="border-b border-paper2 pb-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-tealDeep">Get started</p>
                            <h2 class="mt-1.5 font-display text-2xl font-semibold text-ink">Your application</h2>
                            <p class="mt-1.5 text-sm text-inkSoft">Fields marked <span class="font-semibold text-danger">*</span> are required.</p>
                        </div>

                        <form method="POST" action="" class="mt-6 flex flex-col gap-5">
                            @csrf

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <x-form-input
                                        name="name"
                                        label="Full name *"
                                        placeholder="e.g. Juan Dela Cruz"
                                        autocomplete="name"
                                        required
                                    />
                                </div>

                                <div>
                                    <x-form-input
                                        name="contact"
                                        type="tel"
                                        label="Contact number *"
                                        placeholder="0917 000 0000"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        required
                                    />
                                </div>

                                <div>
                                    <x-form-input
                                        name="email"
                                        type="email"
                                        label="Email address"
                                        placeholder="you@example.com"
                                        autocomplete="email"
                                    />
                                </div>

                                <div>
                                    <x-form-input
                                        name="desired_room"
                                        type="select"
                                        label="Preferred room"
                                        :options="[
                                            '' => 'No preference',
                                            'R101' => 'Room R101',
                                            'R102' => 'Room R102',
                                            'R103' => 'Room R103',
                                            'R104' => 'Room R104',
                                            'R201' => 'Room R201',
                                            'R202' => 'Room R202',
                                            'R203' => 'Room R203',
                                            'R204' => 'Room R204',
                                        ]"
                                    />
                                    <p class="mt-1.5 text-xs text-inkSoft">Room assignment depends on availability.</p>
                                </div>

                                <div>
                                    <x-form-input
                                        name="move_in_date"
                                        type="date"
                                        label="Preferred move-in date"
                                        :value="now()->toDateString()"
                                        :min="now()->toDateString()"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <x-form-input
                                        name="message"
                                        type="textarea"
                                        label="Anything else we should know? (optional)"
                                        rows="4"
                                        placeholder="Share any questions or details that could help us review your request."
                                        maxlength="1000"
                                    />
                                </div>
                            </div>

                            <div class="rounded-xl border border-paper2 bg-paper2/60 px-4 py-3.5">
                                <p class="text-xs leading-relaxed text-inkSoft">
                                    Submitting an application does not create a login. Your contact details will be used to follow up about this room request.
                                </p>
                            </div>

                            <x-button variant="copper" type="submit" block>
                                Submit application
                                <span aria-hidden="true">→</span>
                            </x-button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    @endif
</x-layout>
