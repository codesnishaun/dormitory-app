@props([
    'mode' => 'page',
    'audience' => auth()->user()?->role ?? 'Guest',
    'welcomeMessage' => 'Ask DORA about dormitory services and policies.',
    'suggestions' => [],
    'helpTitle' => 'How DORA can help',
    'helpItems' => [],
    'actionLinks' => [],
])

@php
    $suggestions = $suggestions !== [] ? $suggestions : match (auth()->user()?->role) {
        'admin' => [
            'Summarize room occupancy',
            'Which rooms are currently available?',
            'Show published announcements',
            'Find approved dormitory guidance',
        ],
        'tenant' => [
            'What is my outstanding balance?',
            'Show my finalized electricity bill',
            'Check my maintenance request status',
            'What announcements are available?',
        ],
        default => [
            'Which rooms are currently available?',
            'What announcements are available?',
            'How do I apply for a room?',
            'What are the dormitory rules?',
        ],
    };

    $widget = $mode === 'widget';
    $panelClass = $widget
        ? 'absolute bottom-16 right-0 flex h-[min(42rem,calc(100dvh-6rem))] w-[min(26rem,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border border-paper2 bg-paper3 shadow-2xl shadow-ink/25'
        : 'grid min-h-0 flex-1 grid-rows-[minmax(0,1fr)_auto] gap-3 lg:grid-cols-[minmax(0,1fr)_18rem] lg:grid-rows-1 lg:gap-4';
@endphp

<section
    data-dora-chat
    data-mode="{{ $mode }}"
    data-audience="{{ auth()->user()?->role ?? 'guest' }}"
    data-conversations-url="{{ route('chat.conversations.index') }}"
    data-create-url="{{ route('chat.conversations.store') }}"
    data-show-url="{{ route('chat.conversations.show', '__CONVERSATION__') }}"
    data-delete-url="{{ route('chat.conversations.destroy', '__CONVERSATION__') }}"
    data-message-url="{{ route('chat.messages.store', '__CONVERSATION__') }}"
    data-retry-url="{{ route('chat.messages.retry', ['conversation' => '__CONVERSATION__', 'message' => '__MESSAGE__']) }}"
    aria-label="DORA chat assistant"
    @class([$widget ? 'fixed bottom-4 right-4 z-[60]' : 'flex min-h-0 w-full flex-1 flex-col'])
>
    @if ($widget)
        <button
            type="button"
            data-chat-launcher
            aria-expanded="false"
            aria-controls="dora-chat-panel"
            class="ml-auto flex h-14 items-center gap-3 rounded-full bg-ink px-5 text-sm font-semibold text-paper shadow-lg shadow-ink/25 transition hover:bg-teal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper"
        >
            <x-svg-icon name="assistant" class="h-5 w-5 text-copper" />
            <span>Ask DORA</span>
        </button>
    @endif

    <div
        id="{{ $widget ? 'dora-chat-panel' : 'dora-chat-workspace' }}"
        data-chat-panel
        @if ($widget) hidden @endif
        @class([$panelClass, $widget ? 'mt-3 hidden' : ''])
    >
        <div class="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-paper2 bg-paper3 shadow-sm shadow-ink/5">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-paper2/80 bg-paper3 px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-ink text-copper" aria-hidden="true">
                        <x-svg-icon name="assistant" class="h-6 w-6" />
                    </span>
                    <div>
                        <h2 class="font-display text-lg font-bold leading-tight text-ink">DORA</h2>
                        <p class="mt-0.5 text-xs text-inkSoft">{{ $audience }} assistant · {{ config('dormitory.name') }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span @class([
                        'inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold',
                        'border-teal/25 bg-teal/10 text-tealDeep' => $aiAvailable,
                        'border-copper/25 bg-copper/10 text-copperDeep' => ! $aiAvailable,
                    ])>
                        <span @class([
                            'h-2 w-2 rounded-full',
                            'bg-teal' => $aiAvailable,
                            'bg-copper' => ! $aiAvailable,
                        ]) aria-hidden="true"></span>
                        {{ $aiAvailable ? 'AI ready' : 'AI unavailable' }}
                    </span>
                    @if ($widget)
                        <button
                            type="button"
                            data-chat-close
                            aria-label="Close DORA chat"
                            class="grid h-9 w-9 place-items-center rounded-lg text-inkSoft transition hover:bg-paper2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                        ><x-svg-icon name="close" class="h-4 w-4" /></button>
                    @endif
                </div>
            </header>

            <div class="flex min-h-0 flex-1 flex-col">
                @if ($widget)
                    <div class="flex flex-wrap items-center gap-2 border-b border-paper2/70 bg-paper/50 px-3 py-2.5 sm:px-4">
                    <label class="sr-only" for="dora-conversation-{{ $mode }}">Conversation history</label>
                    <select
                        id="dora-conversation-{{ $mode }}"
                        data-chat-conversation-select
                        class="min-w-0 flex-1 rounded-lg border border-paper2 bg-white px-2.5 py-2 text-xs text-ink outline-none focus-visible:ring-2 focus-visible:ring-teal/30"
                        aria-label="Conversation history"
                    ></select>
                    <button
                        type="button"
                        data-chat-new
                        class="rounded-lg border border-paper2 bg-white px-3 py-2 text-xs font-semibold text-ink transition hover:border-teal/40 hover:bg-teal/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >New chat</button>
                    <button
                        type="button"
                        data-chat-clear
                        class="rounded-lg px-2 py-2 text-xs font-semibold text-inkSoft transition hover:bg-paper2 hover:text-danger focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >Delete chat</button>
                    </div>
                @else
                    <select id="dora-conversation-{{ $mode }}" data-chat-conversation-select hidden aria-label="Conversation history"></select>
                @endif

                <div
                    data-chat-message-list
                    role="log"
                    aria-label="Conversation messages"
                    aria-live="polite"
                    aria-relevant="additions text"
                    class="min-h-0 flex-1 overflow-y-auto overscroll-contain bg-paper/30 px-4 py-5 sm:px-6"
                >
                    <div data-chat-empty class="mx-auto flex min-h-full max-w-xl flex-col justify-center py-6">
                        <div class="text-center">
                            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-teal/10 text-tealDeep" aria-hidden="true">
                                <x-svg-icon name="assistant" class="h-6 w-6" />
                            </span>
                            <p class="mt-4 text-xs font-semibold uppercase tracking-[0.14em] text-tealDeep">{{ $audience }} support</p>
                            <h3 class="mt-1 font-display text-2xl font-semibold tracking-tight text-ink">How can I help?</h3>
                            <p class="mt-2 text-sm leading-relaxed text-inkSoft">{{ $welcomeMessage }}</p>
                        </div>
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            @foreach ($suggestions as $suggestion)
                                <button
                                    type="button"
                                    data-chat-suggestion="{{ $suggestion }}"
                                    class="flex min-h-11 items-center justify-between gap-2 rounded-xl border border-paper2 bg-white/80 px-3 py-2.5 text-left text-xs font-medium text-ink transition hover:border-teal/40 hover:bg-white disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                                >
                                    <span>{{ $suggestion }}</span>
                                    <span class="text-tealDeep" aria-hidden="true">→</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="border-t border-paper2/80 bg-white/70 px-4 py-3 sm:px-5">
                    <p @class([
                        'mb-3 rounded-xl border px-3.5 py-3 text-xs leading-relaxed',
                        'bg-teal/10 text-tealDeep' => $aiAvailable,
                        'bg-copper/10 text-copperDeep' => ! $aiAvailable,
                        'border-teal/15' => $aiAvailable,
                        'border-copper/15' => ! $aiAvailable,
                    ])>
                        @if ($aiAvailable)
                            DORA can check approved guidance and system information available to your account. References below show which source was checked.
                        @else
                            Conversations are saved, but AI replies are disabled until approved knowledge and provider integrations are configured.
                        @endif
                    </p>
                    <div data-chat-status role="alert" aria-live="assertive" aria-atomic="true" hidden class="mb-2 rounded-lg border border-danger/20 bg-danger/5 px-3 py-2 text-xs leading-relaxed text-danger"></div>
                    <form data-chat-composer class="flex items-end gap-2">
                        <label class="sr-only" for="dora-message-{{ $mode }}">Message DORA</label>
                        <textarea
                            id="dora-message-{{ $mode }}"
                            data-chat-input
                            name="message"
                            rows="1"
                            maxlength="4000"
                            autocomplete="off"
                            aria-describedby="dora-input-hint-{{ $mode }}"
                            placeholder="Ask a question..."
                            class="max-h-32 min-h-11 min-w-0 flex-1 resize-y rounded-xl border border-paper2 bg-white px-3 py-2.5 text-sm leading-relaxed text-ink outline-none placeholder:text-inkSoft/65 focus:border-teal/60 focus:ring-2 focus:ring-teal/10 disabled:cursor-wait disabled:bg-paper/60"
                        ></textarea>
                        <button
                            type="submit"
                            data-chat-send
                            disabled
                            aria-label="Send message to DORA"
                            class="inline-flex h-11 shrink-0 items-center gap-2 rounded-xl bg-teal px-4 text-sm font-semibold text-white transition hover:bg-tealDeep disabled:cursor-not-allowed disabled:opacity-45 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                        >
                            <span class="hidden sm:inline">Send</span>
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m22 2-7 20-4-9-9-4Z" />
                                <path d="M22 2 11 13" />
                            </svg>
                        </button>
                    </form>
                    <p id="dora-input-hint-{{ $mode }}" class="mt-2 text-[11px] text-inkSoft/80">Enter to send · Shift + Enter for a new line</p>
                    <p data-chat-typing role="status" aria-live="polite" hidden class="mt-2 flex items-center gap-2 text-xs text-inkSoft">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-teal" aria-hidden="true"></span>
                        DORA is preparing a response…
                    </p>
                    <button
                        type="button"
                        data-chat-retry
                        hidden
                        class="mt-2 rounded-md text-xs font-semibold text-tealDeep underline underline-offset-2 disabled:cursor-wait disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >Retry response</button>
                </div>
            </div>
        </div>

        @if (! $widget)
            <aside class="flex max-h-64 min-h-0 flex-col gap-3 overflow-y-auto overscroll-contain lg:max-h-none lg:gap-4">
                <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-paper2 bg-paper3">
                    <div class="flex items-center justify-between gap-3 border-b border-paper2 px-4 py-3">
                        <div>
                            <h2 class="font-display text-base font-bold text-ink">Conversations</h2>
                            <p class="mt-0.5 text-xs text-inkSoft">Your saved chats</p>
                        </div>
                        <button
                            type="button"
                            data-chat-new
                            aria-label="Start a new conversation"
                            class="shrink-0 rounded-lg bg-ink px-3 py-2 text-xs font-semibold text-paper transition hover:bg-teal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                        >New chat</button>
                    </div>
                    <nav aria-label="DORA conversation history" class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-2">
                        <ul data-chat-conversation-list class="flex flex-col gap-1"></ul>
                        <p data-chat-history-empty class="px-3 py-4 text-center text-xs leading-relaxed text-inkSoft">Your conversations will appear here.</p>
                    </nav>
                    <div class="border-t border-paper2 p-2">
                        <button
                            type="button"
                            data-chat-clear
                            class="w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-inkSoft transition hover:bg-danger/5 hover:text-danger focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                        >Delete current conversation</button>
                    </div>
                </section>

                <section class="hidden rounded-2xl border border-paper2 bg-paper3 p-5 lg:block">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-copper/10 text-copperDeep" aria-hidden="true">
                            <x-svg-icon name="assistant" class="h-4 w-4" />
                        </span>
                        <h2 class="font-display text-base font-bold text-ink">{{ $helpTitle }}</h2>
                    </div>
                    <ul class="mt-4 flex flex-col gap-3">
                        @foreach ($helpItems as $item)
                            <li class="flex gap-2.5 text-sm leading-relaxed text-inkSoft">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal" aria-hidden="true"></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                @if ($actionLinks !== [])
                    <section class="hidden rounded-2xl border border-paper2 bg-paper3 p-5 lg:block">
                        <h2 class="font-display text-base font-bold text-ink">Go to a workspace</h2>
                        <div class="mt-3 flex flex-col gap-2">
                            @foreach ($actionLinks as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    class="flex items-center justify-between gap-3 rounded-xl border border-paper2/80 bg-white/60 px-3.5 py-3 text-sm font-semibold text-ink transition hover:border-teal/40 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                                >
                                    <span>{{ $link['label'] }}</span>
                                    <span class="text-tealDeep" aria-hidden="true">↗</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        @endif
    </div>
</section>
