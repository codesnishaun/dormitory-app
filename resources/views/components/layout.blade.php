<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    @vite('resources/css/app.css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body class="min-h-screen flex flex-col bg-ink font-sans">
    <nav class="sticky top-0 z-20 flex items-center justify-between px-[6vw] py-5 bg-ink/85 backdrop-blur-md backdrop-saturate-150 border-b border-paper/10">
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
            <div class="w-[22px] h-[22px] rounded-full bg-ink"></div>
            </div>
            <div>
            <div class="font-display text-xl font-bold text-paper tracking-tight leading-none">DORA</div>
            <div class="text-[11px] text-paper/55 tracking-widest uppercase -mt-0.5">Cozy Haven Dormitory</div>
            </div>
        </div>
        <div class="flex gap-2.5">
            <button class="px-5 py-2 rounded-full text-sm font-semibold border border-paper/35 text-paper hover:border-paper transition">Apply for a room</button>
            <button class="px-5 py-2 rounded-full text-sm font-semibold text-paper/80 hover:text-paper transition">Log in</button>
        </div>
    </nav>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="bg-ink text-paper/55 px-[6vw] py-8 flex flex-wrap justify-between items-center gap-3 text-[13px] border-t border-paper/[0.08]">
        <div>© 2026 DORA — Dormitory Online Response Assistant, Cozy Haven.</div>
        <div>San Ildefonso, Bulacan · Prototype system</div>
    </footer>

</body>
</html>