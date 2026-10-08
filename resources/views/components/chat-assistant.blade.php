@props([
    'audience',
    'welcomeMessage',
    'suggestions' => [],
    'helpTitle',
    'helpItems' => [],
    'actionLinks' => [],
])

<section
    data-chat-assistant
    aria-label="DORA chat assistant"
    class="grid min-h-[calc(100dvh-12rem)] gap-4 xl:grid-cols-[minmax(0,1fr)_19rem]"
>
    <div class="flex min-h-[34rem] flex-col overflow-hidden rounded-2xl border border-paper2 bg-paper3 shadow-sm shadow-ink/5">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-paper2/80 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-ink text-copper shadow-sm" aria-hidden="true">
                    <x-svg-icon name="assistant" class="h-6 w-6" />
                </span>
                <div>
                    <h2 class="font-display text-lg font-bold leading-tight text-ink">DORA Assistant</h2>
                    <p class="mt-0.5 text-xs text-inkSoft">{{ $audience }} support · Cozy Haven</p>
                </div>
            </div>

            <span class="inline-flex items-center gap-2 rounded-full border border-paper2 bg-white/70 px-3 py-1.5 text-xs font-semibold text-inkSoft">
                <span class="h-2 w-2 rounded-full bg-copper"></span>
                Preview mode
            </span>
        </header>

        <div class="flex flex-1 flex-col justify-center px-4 py-8 sm:px-8">
            <div class="mx-auto flex w-full max-w-2xl flex-col items-center text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-[1.35rem] bg-teal/10 text-tealDeep" aria-hidden="true">
                    <x-svg-icon name="assistant" class="h-7 w-7" />
                </span>
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-tealDeep">Your dormitory assistant</p>
                <h3 class="mt-2 font-display text-3xl font-semibold tracking-tight text-ink sm:text-4xl">Hi, I’m DORA.</h3>
                <p class="mt-3 max-w-lg text-sm leading-relaxed text-inkSoft sm:text-base">{{ $welcomeMessage }}</p>

                <div class="mt-8 w-full text-left">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-[0.12em] text-inkSoft">Try asking</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($suggestions as $suggestion)
                            <button
                                type="button"
                                data-chat-suggestion="{{ $suggestion }}"
                                class="group flex min-h-12 items-center justify-between gap-3 rounded-xl border border-paper2 bg-white/70 px-4 py-3 text-left text-sm font-medium text-ink transition hover:border-teal/40 hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                            >
                                <span>{{ $suggestion }}</span>
                                <span class="text-tealDeep transition group-hover:translate-x-0.5" aria-hidden="true">→</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t border-paper2/80 bg-white/40 p-4 sm:px-6 sm:py-5">
            <form data-chat-composer class="mx-auto w-full max-w-3xl">
                <label for="chat-message" class="sr-only">Message DORA</label>
                <div class="flex items-center gap-2 rounded-2xl border border-paper2 bg-white p-2 shadow-sm transition focus-within:border-teal/60 focus-within:ring-2 focus-within:ring-teal/10">
                    <input
                        id="chat-message"
                        name="message"
                        type="text"
                        autocomplete="off"
                        placeholder="Ask a question about your stay..."
                        class="min-w-0 flex-1 bg-transparent px-2 py-2 text-sm text-ink outline-none placeholder:text-inkSoft/65"
                    >
                    <button
                        type="submit"
                        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl bg-teal px-4 text-sm font-semibold text-white transition hover:bg-tealDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal"
                    >
                        <span class="hidden sm:inline">Send</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m22 2-7 20-4-9-9-4Z" />
                            <path d="M22 2 11 13" />
                        </svg>
                    </button>
                </div>
                <p data-chat-notice role="status" aria-live="polite" class="mt-2 min-h-4 text-center text-xs text-inkSoft">
                    Front-end preview only. Messages are not sent or saved.
                </p>
            </form>
        </div>
    </div>

    <aside class="flex flex-col gap-4">
        <section class="rounded-2xl border border-paper2 bg-paper3 p-5">
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
            <section class="rounded-2xl border border-paper2 bg-paper3 p-5">
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

        <div class="rounded-2xl border border-teal/15 bg-teal/[0.06] p-4">
            <p class="text-xs font-semibold uppercase tracking-[0.1em] text-tealDeep">A quick note</p>
            <p class="mt-1.5 text-xs leading-relaxed text-inkSoft">
                DORA is a front-end preview right now. Connect a chat service to enable real responses.
            </p>
        </div>
    </aside>
</section>

<script>
    (() => {
        const chatAssistant = document.querySelector('[data-chat-assistant]');

        if (!chatAssistant) {
            return;
        }

        const chatInput = chatAssistant.querySelector('#chat-message');
        const chatForm = chatAssistant.querySelector('[data-chat-composer]');
        const chatNotice = chatAssistant.querySelector('[data-chat-notice]');

        chatAssistant.querySelectorAll('[data-chat-suggestion]').forEach((suggestion) => {
            suggestion.addEventListener('click', () => {
                chatInput.value = suggestion.dataset.chatSuggestion;
                chatInput.focus();
                chatNotice.textContent = 'Front-end preview only. Messages are not sent or saved.';
            });
        });

        chatForm.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!chatInput.value.trim()) {
                chatNotice.textContent = 'Enter a message or choose a suggested prompt.';
                chatInput.focus();

                return;
            }

            chatNotice.textContent = 'This preview is not connected yet, so your message was not sent or saved.';
        });
    })();
</script>
