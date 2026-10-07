@props([
    'bodyClass' => 'min-h-screen flex flex-col bg-ink font-sans',
    'navigation' => 'guest',
])

<!DOCTYPE html>
<html lang="en" class="{{ $navigation !== 'guest' ? 'h-full overflow-hidden' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'DORA' }}</title>
    @vite('resources/css/app.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body class="{{ $bodyClass }}">
    @if ($navigation === 'guest')
        <nav class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-paper/10 bg-ink/90 px-4 py-3 backdrop-blur-md backdrop-saturate-150 sm:px-[6vw] sm:py-4">
            <a href="{{ route('home') }}" class="shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
                        <div class="h-[22px] w-[22px] rounded-full bg-ink"></div>
                    </div>
                    <div>
                        <div class="font-display text-xl font-bold leading-none tracking-tight text-paper">DORA</div>
                        <div class="mt-0.5 text-[9px] uppercase tracking-[0.14em] text-paper/55 sm:text-[11px] sm:tracking-widest">B&A Dormitory</div>
                    </div>
                </div>
            </a>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2.5">
                <x-button
                    variant="navigation"
                    size="sm"
                    :href="route('application_form')"
                    :active="request()->routeIs('application_form')"
                    class="whitespace-nowrap !px-3 sm:!px-4"
                >Apply<span class="hidden sm:inline"> for a room</span></x-button>

                <x-button
                    variant="navigation"
                    size="sm"
                    :href="route('login')"
                    :active="request()->routeIs('login')"
                    class="whitespace-nowrap !px-3 sm:!px-4"
                >Log in</x-button>
            </div>
        </nav>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-paper/[0.08] bg-ink px-[6vw] py-8 text-[13px] text-paper/55">
            <div>© 2026 DORA — Dormitory Online Response Assistant, B&A Dormitory.</div>
            <div>San Ildefonso, Bulacan · Prototype system</div>
        </footer>
    @else
    <div class="flex h-full min-h-0 flex-col overflow-hidden">
        <div class="flex min-h-0 flex-1 flex-col md:flex-row">

            {{-- Sidebar --}}
            <aside class="relative z-10 flex w-full shrink-0 flex-col border-b border-paper/10 bg-ink px-4 py-3 md:min-h-0 md:w-70 md:border-b-0 md:border-r md:px-5 md:py-5">
                <a href="{{ route('home') }}" class="mb-3 flex items-center gap-2.5 md:mb-6">
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
                        class="flex cursor-pointer items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-paper/80 hover:bg-paper/10 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-copper peer-checked:[&_.menu-label]:hidden peer-checked:[&_.close-label]:inline peer-checked:[&_.menu-icon]:hidden peer-checked:[&_.close-icon]:block md:hidden"
                    >
                        <span class="menu-label">Menu</span>
                        <span class="close-label hidden">Close</span>
                        <x-svg-icon name="menu" class="menu-icon h-5 w-5" />
                        <x-svg-icon name="close" class="close-icon hidden h-5 w-5" />
                    </label>

                    <div id="sidebar-navigation" class="hidden flex-col gap-3 border-t border-paper/10 pt-4 peer-checked:flex md:flex md:flex-1 md:border-t-0 md:pt-0">
                        <nav
                            aria-label="{{ ucfirst($navigation) }} navigation"
                            class="flex min-h-0 flex-1 flex-col gap-2"
                        >
                            {{ $sidebar }}
                        </nav>
                    </div>
                </div>
            </aside>

            {{-- Main --}}
            <main class="min-h-0 min-w-0 flex-1 overflow-y-auto overscroll-contain bg-paper p-4 sm:p-8">
                {{ $slot }}
            </main>

        </div>

    </div>
    @endif
</body>
</html>
