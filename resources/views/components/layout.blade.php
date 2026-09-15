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
<body class="">
    <header>
        <nav class="sticky top-0 z-20 flex items-center justify-between px-[6vw] py-5 bg-[#16231C]/85 backdrop-blur-md backdrop-saturate-150 border-b border-[#F6EEDD]/10">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center bg-[conic-gradient(#C1712F_0deg_260deg,rgba(246,238,221,.18)_260deg_360deg)]">
                    <div class="w-[22px] h-[22px] rounded-full bg-[#16231C]"></div>
                </div>
                <div>
                    <div class="font-[Fraunces] text-xl font-bold text-[#F6EEDD] tracking-tight leading-none">DORA</div>
                    <div class="text-[11px] text-[#F6EEDD]/55 tracking-widest uppercase mt-1">Cozy Haven Dormitory</div>
                </div>
            </div>
            <div class="flex gap-2.5">
                <button class="px-5 py-2 rounded-full text-sm font-semibold border border-[#F6EEDD]/35 text-[#F6EEDD] hover:border-[#F6EEDD] transition bg-[#204E4D]">Apply for a room</button>
                <button class="px-5 py-2 rounded-full text-sm font-semibold text-[#F6EEDD]/80 hover:text-[#F6EEDD] transition">Log in</button>
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>
</body>
</html>