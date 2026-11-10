@props([
    'bodyClass' => 'min-h-screen flex flex-col bg-ink font-sans',
    'navigation' => 'guest',
])

<!DOCTYPE html>
<html lang="en" class="{{ $navigation !== 'guest' && $navigation !== 'none' ? 'h-full overflow-hidden' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'DORA' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body class="{{ $bodyClass }}">
    @if ($navigation === 'guest')
        <header class="sticky top-0 z-30 border-b border-paper2/80 bg-paper3/95 backdrop-blur">
            <nav aria-label="Main navigation" class="relative mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:gap-5 sm:px-8 sm:py-4 lg:px-12">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-ink" aria-hidden="true">
                        <span class="h-3 w-3 rounded-full bg-copper"></span>
                    </span>
                    <span>
                        <span class="block font-display text-xl font-bold leading-none tracking-tight text-ink">DORA</span>
                        <span class="mt-1 block max-w-32 truncate text-[9px] font-semibold uppercase tracking-[0.12em] text-inkSoft sm:max-w-40">{{ config('dormitory.name') }}</span>
                    </span>
                </a>

                <div class="hidden items-center gap-6 lg:flex">
                    <a href="{{ route('home') }}" class="text-sm font-medium text-inkSoft transition hover:text-tealDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">Home</a>
                    <a href="{{ route('home') }}#about" class="text-sm font-medium text-inkSoft transition hover:text-tealDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">About</a>
                    <a href="{{ route('home') }}#rooms" class="text-sm font-medium text-inkSoft transition hover:text-tealDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">Rooms</a>
                    <a href="{{ route('home') }}#resident-services" class="text-sm font-medium text-inkSoft transition hover:text-tealDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">Resident services</a>
                    <a href="{{ route('home') }}#location" class="text-sm font-medium text-inkSoft transition hover:text-tealDeep focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-teal">Location</a>
                </div>

                <div class="hidden shrink-0 items-center gap-2 lg:flex">
                    @auth
                        @if (auth()->user()->role === 'admin')
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-sm font-semibold text-ink transition hover:bg-paper2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Dashboard</a>
                        @else
                            <a href="{{ route('tenant.dashboard') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-sm font-semibold text-ink transition hover:bg-paper2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Dashboard</a>
                        @endif
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex min-h-10 items-center rounded-xl px-4 text-sm font-semibold text-inkSoft transition hover:bg-paper2 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center rounded-xl px-4 text-sm font-semibold text-ink transition hover:bg-paper2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Tenant login</a>
                        <a href="{{ route('application_form') }}" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-ink px-5 text-sm font-semibold text-paper3 transition hover:bg-inkSoft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Apply now</a>
                    @endauth
                </div>

                <details data-mobile-navigation class="group relative ml-auto lg:hidden">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-xl border border-paper2 px-4 text-sm font-semibold text-ink transition hover:bg-paper active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal [&::-webkit-details-marker]:hidden">
                        Menu
                        <svg class="h-4 w-4 transition-transform group-open:rotate-180" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                            <path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </summary>
                    <div class="absolute right-2 top-[calc(100%+0.75rem)] z-40 w-[min(20rem,calc(100vw-3rem))] rounded-2xl border border-paper2 bg-paper3 p-3 shadow-xl shadow-ink/15">
                        <nav aria-label="Mobile navigation" class="flex flex-col gap-1">
                            <a href="{{ route('home') }}" class="rounded-lg px-3 py-2.5 text-sm font-medium text-inkSoft hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Home</a>
                            <a href="{{ route('home') }}#about" class="rounded-lg px-3 py-2.5 text-sm font-medium text-inkSoft hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">About</a>
                            <a href="{{ route('home') }}#rooms" class="rounded-lg px-3 py-2.5 text-sm font-medium text-inkSoft hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Rooms</a>
                            <a href="{{ route('home') }}#resident-services" class="rounded-lg px-3 py-2.5 text-sm font-medium text-inkSoft hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Resident services</a>
                            <a href="{{ route('home') }}#location" class="rounded-lg px-3 py-2.5 text-sm font-medium text-inkSoft hover:bg-paper hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Location</a>
                        </nav>
                        <div class="mt-2 flex flex-col gap-2 border-t border-paper2 pt-3">
                            @auth
                                @if (auth()->user()->role === 'admin')
                                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-ink hover:bg-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Dashboard</a>
                                @else
                                    <a href="{{ route('tenant.dashboard') }}" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-ink hover:bg-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Dashboard</a>
                                @endif
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-inkSoft hover:bg-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Log out</button>
                                </form>
                            @else
                                <a href="{{ route('application_form') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-ink px-4 text-sm font-semibold text-paper3 transition hover:bg-inkSoft active:scale-[0.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Apply now</a>
                                <a href="{{ route('login') }}" class="rounded-lg px-3 py-2.5 text-center text-sm font-semibold text-ink hover:bg-paper focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">Login</a>
                            @endauth
                        </div>
                    </div>
                </details>
            </nav>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-ink px-5 py-10 text-paper3 sm:px-8 lg:px-12">
            <div class="mx-auto grid max-w-7xl gap-8 border-b border-paper/15 pb-8 sm:grid-cols-2 lg:grid-cols-[1.3fr_1fr_1fr]">
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-paper3">
                        <span class="grid h-8 w-8 place-items-center rounded-full border border-paper/30" aria-hidden="true">
                            <span class="h-2.5 w-2.5 rounded-full bg-copper"></span>
                        </span>
                        <span class="font-display text-lg font-semibold">DORA</span>
                    </a>
                    <p class="mt-3 text-sm text-paper/70">Dormitory Online Response Assistant</p>
                    <p class="mt-1 text-sm font-medium">{{ config('dormitory.name') }}</p>
                </div>
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-paper/55">Location</h2>
                    <p class="mt-3 max-w-xs text-sm leading-relaxed text-paper/80">{{ config('dormitory.address') }}</p>
                </div>
                <nav aria-label="Footer navigation">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-paper/55">Explore</h2>
                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                        <a href="{{ route('home') }}#about" class="rounded-sm text-paper/80 transition hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-paper3">About</a>
                        <a href="{{ route('home') }}#rooms" class="rounded-sm text-paper/80 transition hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-paper3">Rooms</a>
                        <a href="{{ route('home') }}#location" class="rounded-sm text-paper/80 transition hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-paper3">Location</a>
                        <a href="{{ route('application_form') }}" class="rounded-sm text-paper/80 transition hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-paper3">Apply</a>
                        <a href="{{ route('login') }}" class="rounded-sm text-paper/80 transition hover:text-paper focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-paper3">Tenant login</a>
                    </div>
                </nav>
            </div>
            <p class="mx-auto max-w-7xl pt-5 text-xs text-paper/55">© {{ now()->year }} DORA · {{ config('dormitory.name') }}</p>
        </footer>
    @elseif ($navigation !== 'none')
    <div class="flex h-dvh min-h-0 flex-col overflow-hidden">
        <div class="flex min-h-0 flex-1 flex-col md:flex-row">

            {{-- Sidebar --}}
            <aside class="relative z-10 flex max-h-dvh w-full shrink-0 flex-col overflow-y-auto overscroll-contain border-b border-paper/10 bg-gradient-to-b from-ink to-[#1D2D24] px-4 py-3 shadow-lg md:min-h-0 md:max-h-none md:w-72 md:border-b-0 md:border-r md:border-paper/10 md:px-5 md:py-6">
                <a href="{{ route('home') }}" class="mb-3 flex items-center gap-3 md:mb-7">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
                        <span class="h-[22px] w-[22px] rounded-full bg-ink"></span>
                    </span>

                    <span>
                        <span class="block font-display text-xl font-bold leading-none tracking-tight text-paper">
                            DORA
                        </span>

                        <span class="mt-0.5 block text-[11px] uppercase tracking-widest text-paper/55">
                            B&A Dormitory
                        </span>
                    </span>
                    <span class="ml-auto hidden rounded-full border border-paper/10 bg-paper/[0.06] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-paper/55 md:inline-flex">
                        {{ $navigation === 'admin' ? 'Admin' : 'Resident' }}
                    </span>
                </a>

                <div class="flex flex-col md:flex-1">
                    <input
                        id="sidebar-menu-toggle"
                        type="checkbox"
                        class="peer sr-only md:hidden"
                        aria-controls="sidebar-navigation"
                    >
                    <label
                        for="sidebar-menu-toggle"
                        class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-xl border border-paper/10 bg-paper/[0.04] px-3.5 py-2.5 text-sm font-semibold text-paper/85 transition hover:bg-paper/[0.08] peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-copper peer-checked:[&_.menu-label]:hidden peer-checked:[&_.close-label]:inline peer-checked:[&_.menu-icon]:hidden peer-checked:[&_.close-icon]:block md:hidden"
                    >
                        <span class="menu-label">Navigation</span>
                        <span class="close-label hidden">Close</span>
                        <x-svg-icon name="menu" class="menu-icon h-5 w-5" />
                        <x-svg-icon name="close" class="close-icon hidden h-5 w-5" />
                    </label>

                    <div id="sidebar-navigation" class="hidden flex-col gap-3 border-t border-paper/10 pt-4 peer-checked:flex md:flex md:flex-1 md:border-t-0 md:pt-0">
                        <nav
                            aria-label="{{ ucfirst($navigation) }} navigation"
                            class="flex min-h-0 flex-1 flex-col gap-1.5"
                        >
                            {{ $sidebar }}
                        </nav>
                    </div>
                </div>
            </aside>

            {{-- Main --}}
            <main @class([
                'min-h-0 min-w-0 flex-1 overscroll-contain bg-paper p-4 sm:p-8',
                'flex flex-col overflow-hidden' => request()->routeIs('admin.ask-dora', 'tenant.ask-dora'),
                'overflow-y-auto' => ! request()->routeIs('admin.ask-dora', 'tenant.ask-dora'),
            ])>
                {{ $slot }}
            </main>

        </div>

    </div>
    @else
        {{ $slot }}
    @endif
    <x-chat-assistant
        mode="widget"
        :audience="auth()->user()?->role ?? 'Guest'"
    />
    @stack('scripts')
</body>
</html>
