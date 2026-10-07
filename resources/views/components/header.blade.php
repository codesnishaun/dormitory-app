@props([
    'title' => null,
    'description' => null,
])

<header class="mb-7">
    <h1 class="font-display text-3xl font-bold text-ink">{{ $title ?? 'Dashboard' }}</h1>
    <p class="mt-1 text-sm text-inkSoft font-sans">{{ $description ?? 'Welcome back — here is what is new at Cozy Haven.' }}</p>
</header>