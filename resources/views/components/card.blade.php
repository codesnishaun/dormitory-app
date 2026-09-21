
@props([
    'icon' => '👥',
    'title',
    'description',
    'iconBg' => 'bg-copper/[0.14]',
    'iconColor' => 'text-copperDeep',
])

<div {{ $attributes->merge([
    'class' => 'bg-paper3 border border-ink/[0.08] rounded-2xl p-6 hover:-translate-y-1 hover:shadow-lg transition'
]) }}>
    <div class="w-9 h-9 rounded-[10px] flex items-center justify-center mb-4 text-lg {{ $iconBg }} {{ $iconColor }}">
        {{ $icon }}
    </div>

    <h4 class="text-base font-semibold text-ink mb-2">
        {{ $title }}
    </h4>

    <p class="text-[13.5px] text-inkSoft leading-relaxed">
        {{ $description }}
    </p>
</div>

