@props(['name'])

<svg
    {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }}
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('dashboard')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
            @break

        @case('tenants')
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="10" cy="7" r="4" />
            <path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            @break

        @case('rooms')
            <path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z" />
            @break

        @case('billing')
            <path d="M4 3h16v18l-4-2-4 2-4-2-4 2z" />
            <path d="M8 8h8M8 12h8M8 16h3" />
            @break

        @case('announcements')
            <path d="m3 11 18-5v12L3 13z" />
            <path d="M11.6 15.4 14 21h-4l-2.4-7.2M21 11v2" />
            @break

        @case('maintenance')
            <path d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18l3 3 5.3-5.3a5 5 0 0 0 6.4-6.4L14 13l-3-3z" />
            @break

        @case('assistant')
            <path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1" />
            <circle cx="12" cy="12" r="4" />
            @break

        @case('logout')
            <path d="M10 17l5-5-5-5M15 12H3" />
            <path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6" />
            @break

        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break

        @case('close')
            <path d="m18 6-12 12M6 6l12 12" />
            @break
    @endswitch
</svg>
