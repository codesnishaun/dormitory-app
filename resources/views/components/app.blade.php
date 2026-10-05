@props([
    'bodyClass' => 'min-h-screen flex flex-col bg-ink font-sans',
    'navigation' => 'guest',
])

<!DOCTYPE html>
<html lang="en">
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
        <nav class="sticky top-0 z-20 flex items-center justify-between border-b border-paper/10 bg-ink/85 px-[6vw] py-5 backdrop-blur-md backdrop-saturate-150">
            <a href="{{ route('home') }}">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
                        <div class="h-[22px] w-[22px] rounded-full bg-ink"></div>
                    </div>
                    <div>
                        <div class="font-display text-xl font-bold leading-none tracking-tight text-paper">DORA</div>
                        <div class="-mt-0.5 text-[11px] uppercase tracking-widest text-paper/55">Cozy Haven Dormitory</div>
                    </div>
                </div>
            </a>

            <div class="flex gap-2.5">
                <x-button
                    variant="navigation"
                    :href="route('application_form')"
                    :active="request()->routeIs('application_form')"
                >Apply for a room</x-button>

                <x-button
                    variant="navigation"
                    :href="route('login')"
                    :active="request()->routeIs('login')"
                >Log in</x-button>
            </div>
        </nav>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-paper/[0.08] bg-ink px-[6vw] py-8 text-[13px] text-paper/55">
            <div>© 2026 DORA — Dormitory Online Response Assistant, Cozy Haven.</div>
            <div>San Ildefonso, Bulacan · Prototype system</div>
        </footer>
    @else
    <div class="flex h-screen flex-col overflow-hidden">
        <div class="flex min-h-0 flex-1 flex-col md:flex-row">

            {{-- Sidebar --}}
            <aside class="flex min-h-0 w-full shrink-0 flex-col overflow-y-auto border-b border-paper/10 bg-ink px-5 py-5 md:w-70 md:border-b-0 md:border-r">
                <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
                        <div class="h-[22px] w-[22px] rounded-full bg-ink"></div>
                    </div>

                    <div>
                        <div class="font-display text-xl font-bold leading-none tracking-tight text-paper">
                            DORA
                        </div>

                        <div class="-mt-0.5 text-[11px] uppercase tracking-widest text-paper/55">
                            Cozy Haven Dormitory
                        </div>
                    </div>
                </a>

                <nav
                    aria-label="{{ ucfirst($navigation) }} navigation"
                    class="flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto"
                >
                    {{ $navigation }}
                    {{ $sidebar }}
                </nav>
            </aside>

            {{-- Main --}}
            <main class="min-h-0 min-w-0 flex-1 overflow-y-auto bg-paper p-5 sm:p-8">
                {{ $slot }}
            </main>

        </div>

    </div>
    @endif
</body>
</html>
