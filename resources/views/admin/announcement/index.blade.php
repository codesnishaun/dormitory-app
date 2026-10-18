<x-admin-layout>
    <x-slot:title>Announcements</x-slot:title>

    @php
        $field = 'h-11 w-full rounded-xl border border-ink/10 bg-white/70 px-3.5 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15';
        $publishMode = old('publish_mode', ($editing && $editing->publish_at->isFuture()) ? 'schedule' : 'now');
    @endphp

    <div class="mx-auto w-full space-y-6 pb-4 sm:space-y-8">
        {{-- Page title and context. --}}
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="space-y-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-teal/15 bg-teal/8 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-teal">
                    <span class="h-1.5 w-1.5 rounded-full bg-teal"></span>
                    Resident updates
                </span>
                <div>
                    <h1 class="font-display text-3xl font-bold tracking-tight text-ink sm:text-4xl">Announcements</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-inkSoft sm:text-base">
                        Share important updates with every resident, keep key notices visible, and plan what goes out next.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-start rounded-xl border border-ink/8 bg-paper3 px-3.5 py-2.5 text-xs text-inkSoft sm:self-auto">
                <svg class="h-4 w-4 shrink-0 text-teal" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3.5 5.5h13m-11 0v10h9v-10m-6 3v4m3-4v4M7 3h6l.5 2.5h-7L7 3Z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span><span class="font-semibold text-ink">House-wide</span> audience</span>
            </div>
        </header>

        @if (session('success'))
            <div class="rounded-xl border border-success/20 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Status overview. --}}
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4" aria-label="Announcement summary">
            <article class="rounded-2xl border border-ink/8 bg-paper3 p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Published</h2>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-teal/10 text-teal">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M4 3.5h8l4 4v9H4zM12 3.5v4h4M7 11h6M7 14h6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-ink">{{ count($publishedAnnouncements) }}</p>
                <p class="mt-1 text-xs text-inkSoft">Visible to residents</p>
            </article>

            <article class="rounded-2xl border border-copper/20 bg-copper/5 p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Pinned</h2>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-copper/12 text-copperDeep">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="m12.5 3 4.5 4.5-2.5 1.5-.5 4-2 2-2.5-5-3.5 5.5m6-7.5 3-3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-ink">{{ $pinnedCount }}</p>
                <p class="mt-1 text-xs text-inkSoft">At the top of the board</p>
            </article>

            <article class="col-span-2 rounded-2xl border border-ink/8 bg-paper3 p-4 sm:col-span-1 sm:p-5">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-inkSoft">Scheduled</h2>
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-success/10 text-success">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="10" cy="10" r="7" />
                            <path d="M10 6v4l2.5 1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                <p class="mt-2 font-display text-2xl font-bold text-ink">{{ count($scheduledAnnouncements) }}</p>
                <p class="mt-1 text-xs text-inkSoft">Ready for a future date</p>
            </article>
        </section>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(360px,1fr)]">
            {{-- Compose / Edit form. --}}
            <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-[0_4px_18px_rgba(22,35,28,0.035)]" aria-labelledby="compose-heading">
                <div class="flex items-start gap-3 border-b border-ink/8 px-4 py-4 sm:px-6">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-copper/12 text-copperDeep">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M10 4v12m6-6H4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <div>
                        <h2 id="compose-heading" class="font-display text-xl font-bold text-ink">
                            {{ $editing ? 'Edit announcement' : 'Write an announcement' }}
                        </h2>
                        <p class="mt-1 text-xs leading-5 text-inkSoft">Be clear, practical, and include when a change takes effect.</p>
                    </div>
                </div>

                <form id="compose" method="POST"
                      action="{{ $editing ? route('admin.announcement.update', $editing) : route('admin.announcement.store') }}"
                      class="space-y-4 p-4 sm:space-y-5 sm:p-6">
                    @csrf
                    @if ($editing)
                        @method('PUT')
                    @endif

                    @if ($errors->any())
                        <div class="rounded-xl border border-danger/20 bg-danger/5 p-3 text-sm text-danger">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block space-y-1.5">
                            <span class="text-xs font-semibold text-ink">Announcement title</span>
                            <input type="text" name="title" value="{{ old('title', $editing->title ?? '') }}"
                                   placeholder="e.g. Water service update for Monday" class="{{ $field }}">
                        </label>
                        <label class="block space-y-1.5">
                            <span class="text-xs font-semibold text-ink">Category</span>
                            <select name="category" class="{{ $field }}">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" @selected(old('category', $editing->category ?? '') === $cat)>{{ $cat }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <label class="block space-y-1.5">
                        <span class="flex items-center justify-between gap-3 text-xs font-semibold text-ink">
                            <span>Message</span>
                            <span class="font-normal text-inkSoft">Keep important details up front</span>
                        </span>
                        <textarea name="body" rows="5"
                                  placeholder="What do residents need to know? Include dates, times, and any next steps."
                                  class="w-full resize-y rounded-xl border border-ink/10 bg-white/70 px-3.5 py-3 text-sm leading-6 text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">{{ old('body', $editing->body ?? '') }}</textarea>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block space-y-1.5">
                            <span class="text-xs font-semibold text-ink">Publish</span>
                            <select name="publish_mode" id="publish_mode" class="{{ $field }}">
                                <option value="now" @selected($publishMode === 'now')>Publish now</option>
                                <option value="schedule" @selected($publishMode === 'schedule')>Schedule for later</option>
                            </select>
                        </label>
                        <label class="block space-y-1.5">
                            <span class="text-xs font-semibold text-ink">Optional end date</span>
                            <input type="date" name="ends_at"
                                   value="{{ old('ends_at', optional($editing?->ends_at)->format('Y-m-d')) }}"
                                   class="{{ $field }}">
                        </label>
                    </div>

                    <label id="schedule_wrap" class="block space-y-1.5 {{ $publishMode === 'schedule' ? '' : 'hidden' }}">
                        <span class="text-xs font-semibold text-ink">Schedule date and time</span>
                        <input type="datetime-local" name="publish_at"
                               value="{{ old('publish_at', ($editing && $editing->publish_at->isFuture()) ? $editing->publish_at->format('Y-m-d\TH:i') : '') }}"
                               class="{{ $field }}">
                    </label>

                    <div class="flex flex-col gap-4 border-t border-ink/8 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1"
                                   @checked(old('is_pinned', $editing->is_pinned ?? false))
                                   class="mt-0.5 h-4 w-4 rounded border-ink/20 accent-teal focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal">
                            <span>
                                <span class="block text-sm font-semibold text-ink">Pin this announcement</span>
                                <span class="mt-0.5 block text-xs leading-5 text-inkSoft">Keep an important notice near the top of the resident board.</span>
                            </span>
                        </label>

                        <div class="flex items-center gap-2">
                            @if ($editing)
                                <a href="{{ route('admin.announcement.index') }}"
                                   class="inline-flex h-11 items-center justify-center rounded-xl border border-ink/12 px-4 text-sm font-semibold text-ink transition hover:bg-teal/5">
                                    Cancel
                                </a>
                            @endif
                            <button type="submit"
                                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-copper px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-copperDeep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-copper">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                    <path d="m3 9 14-6-6 14-2-6-6-2Zm6 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                {{ $editing ? 'Save changes' : 'Post announcement' }}
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <div class="space-y-6">
                {{-- Writing tips. --}}
                <aside class="rounded-2xl border border-teal/15 bg-teal/5 p-4 sm:p-5" aria-labelledby="writing-tips-heading">
                    <div class="flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal/10 text-teal">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M10 2.75a6 6 0 0 0-3.5 10.87c.75.54 1 1.15 1 1.88h5c0-.73.25-1.34 1-1.88A6 6 0 0 0 10 2.75ZM8 17h4m-3.5-2h3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <h2 id="writing-tips-heading" class="font-display text-base font-bold text-ink">Make notices easy to act on</h2>
                    </div>
                    <ul class="mt-4 grid gap-3 text-sm text-inkSoft">
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal"></span>
                            <span><strong class="font-semibold text-ink">Lead with the change.</strong> Put the event or service affected in the title.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal"></span>
                            <span><strong class="font-semibold text-ink">Include the when.</strong> Add a date and time residents can plan around.</span>
                        </li>
                        <li class="flex gap-2.5">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-teal"></span>
                            <span><strong class="font-semibold text-ink">Explain what to do.</strong> End with a clear action, contact, or next step.</span>
                        </li>
                    </ul>
                </aside>

                {{-- Scheduled notices. --}}
                <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3" aria-labelledby="scheduled-heading">
                    <div class="flex items-center justify-between gap-3 border-b border-ink/8 px-4 py-4 sm:px-5">
                        <div>
                            <h2 id="scheduled-heading" class="font-display text-lg font-bold text-ink">Coming up</h2>
                            <p class="mt-1 text-xs text-inkSoft">Scheduled notices</p>
                        </div>
                        <span class="rounded-full bg-success/10 px-2.5 py-1 text-[11px] font-semibold text-success">{{ count($scheduledAnnouncements) }} queued</span>
                    </div>
                    <ul class="divide-y divide-ink/6">
                        @forelse ($scheduledAnnouncements as $announcement)
                            <li class="flex items-start gap-3 px-4 py-4 sm:px-5">
                                <span class="flex h-9 w-9 shrink-0 flex-col items-center justify-center rounded-lg bg-paper px-1 text-center">
                                    <span class="text-[9px] font-bold uppercase tracking-wide text-inkSoft">{{ \Illuminate\Support\Carbon::parse($announcement['date'])->format('M') }}</span>
                                    <span class="font-display text-sm font-bold leading-none text-ink">{{ \Illuminate\Support\Carbon::parse($announcement['date'])->format('d') }}</span>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold leading-snug text-ink">{{ $announcement['title'] }}</span>
                                    <span class="mt-1 block text-xs text-inkSoft">{{ $announcement['date'] }} · {{ $announcement['time'] }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <span class="hidden rounded-full bg-paper px-2.5 py-1 text-[10px] font-semibold text-inkSoft sm:inline-flex">{{ $announcement['category'] }}</span>
                                    <a href="{{ route('admin.announcement.index', ['edit' => $announcement['id']]) }}#compose"
                                       class="text-[11px] font-semibold text-teal hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.announcement.destroy', $announcement['id']) }}"
                                          onsubmit="return confirm('Delete this scheduled announcement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-[11px] font-semibold text-danger hover:underline">Delete</button>
                                    </form>
                                </span>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-sm text-inkSoft sm:px-5">Nothing scheduled.</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>

        {{-- Published notices. --}}
        <section class="overflow-hidden rounded-2xl border border-ink/8 bg-paper3 shadow-[0_4px_18px_rgba(22,35,28,0.035)]" aria-labelledby="published-heading">
            <div class="flex flex-col gap-3 border-b border-ink/8 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="published-heading" class="font-display text-xl font-bold text-ink">Published announcements</h2>
                        <span class="rounded-full bg-paper px-2.5 py-1 text-[11px] font-semibold text-inkSoft">{{ count($publishedAnnouncements) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-inkSoft">The latest notices currently visible to residents.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <label class="relative block min-w-0 flex-1 sm:w-56 sm:flex-none">
                        <span class="sr-only">Search announcements</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-inkSoft/70" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <circle cx="8.75" cy="8.75" r="5.75" />
                            <path d="m13 13 4 4" stroke-linecap="round" />
                        </svg>
                        <input id="notice_search" type="search" placeholder="Search notices"
                               class="h-10 w-full rounded-lg border border-ink/10 bg-white/70 pl-9 pr-3 text-sm text-ink outline-none transition placeholder:text-inkSoft/55 focus:border-teal focus:ring-2 focus:ring-teal/15">
                    </label>
                    <label>
                        <span class="sr-only">Filter by category</span>
                        <select id="notice_filter"
                                class="h-10 w-full rounded-lg border border-ink/10 bg-white/70 px-3 text-sm text-ink outline-none transition focus:border-teal focus:ring-2 focus:ring-teal/15 sm:w-44">
                            <option value="">All categories</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="grid gap-3 p-3 sm:gap-4 sm:p-5">
                @forelse ($publishedAnnouncements as $announcement)
                    <article data-notice
                             data-category="{{ $announcement['category'] }}"
                             data-text="{{ strtolower($announcement['title'] . ' ' . $announcement['body']) }}"
                             class="rounded-xl border p-4 transition hover:shadow-sm sm:p-5 {{ $announcement['pinned'] ? 'border-copper/60 bg-copper/5' : 'border-ink/8 bg-white/25 hover:border-ink/15' }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($announcement['pinned'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-copper/12 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-copperDeep">
                                            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                                <path d="m12.5 3 4.5 4.5-2.5 1.5-.5 4-2 2-2.5-5-3.5 5.5m6-7.5 3-3" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            Pinned
                                        </span>
                                    @endif
                                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $announcement['tone'] === 'copper' ? 'bg-copper/10 text-copperDeep' : ($announcement['tone'] === 'teal' ? 'bg-teal/10 text-tealDeep' : ($announcement['tone'] === 'success' ? 'bg-success/10 text-success' : 'bg-ink/5 text-inkSoft')) }}">
                                        {{ $announcement['category'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-success">
                                        <span class="h-1.5 w-1.5 rounded-full bg-success"></span>
                                        Published
                                    </span>
                                </div>
                                <h3 class="mt-2 font-display text-base font-bold leading-snug text-ink sm:text-lg">{{ $announcement['title'] }}</h3>
                                <p class="mt-1 text-[11px] text-inkSoft">{{ $announcement['date'] }} <span aria-hidden="true">·</span> {{ $announcement['author'] }}</p>
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-inkSoft">{{ $announcement['body'] }}</p>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 border-t border-ink/8 pt-3 sm:border-0 sm:pt-0">
                                <form method="POST" action="{{ route('admin.announcement.pin', $announcement['id']) }}" class="flex flex-1 sm:flex-none">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-ink/12 px-3 text-xs font-semibold text-ink transition hover:border-teal/40 hover:bg-teal/5">
                                        {{ $announcement['pinned'] ? 'Unpin' : 'Pin' }}
                                    </button>
                                </form>

                                <a href="{{ route('admin.announcement.index', ['edit' => $announcement['id']]) }}#compose"
                                   class="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-ink/12 px-3 text-xs font-semibold text-ink transition hover:border-teal/40 hover:bg-teal/5 sm:flex-none">
                                    Edit
                                </a>

                                <form method="POST" action="{{ route('admin.announcement.destroy', $announcement['id']) }}"
                                      onsubmit="return confirm('Delete this announcement?')" class="flex flex-1 sm:flex-none">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 flex-1 items-center justify-center rounded-lg border border-danger/20 px-3 text-xs font-semibold text-danger transition hover:bg-danger/5">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="py-6 text-center text-sm text-inkSoft">No published announcements yet.</p>
                @endforelse

                <p id="no_match" class="hidden py-6 text-center text-sm text-inkSoft">No announcements match your search or filter.</p>
            </div>

            <div class="flex flex-col gap-1 border-t border-ink/8 bg-paper/35 px-4 py-3 text-xs text-inkSoft sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <span>Showing {{ count($publishedAnnouncements) }} announcements</span>
            </div>
        </section>
    </div>

    <script>
        // Schedule field: lalabas lang kapag "Schedule for later"
        const publishMode = document.getElementById('publish_mode');
        const scheduleWrap = document.getElementById('schedule_wrap');
        publishMode.addEventListener('change', () => {
            scheduleWrap.classList.toggle('hidden', publishMode.value !== 'schedule');
        });

        // Search at category filter
        const searchBox = document.getElementById('notice_search');
        const filterBox = document.getElementById('notice_filter');
        const notices = document.querySelectorAll('[data-notice]');
        const noMatch = document.getElementById('no_match');

        function applyFilters() {
            const term = searchBox.value.trim().toLowerCase();
            const cat = filterBox.value;
            let visible = 0;

            notices.forEach((el) => {
                const matchText = !term || el.dataset.text.includes(term);
                const matchCat = !cat || el.dataset.category === cat;
                const show = matchText && matchCat;
                el.classList.toggle('hidden', !show);
                if (show) visible++;
            });

            noMatch.classList.toggle('hidden', visible > 0 || notices.length === 0);
        }

        searchBox.addEventListener('input', applyFilters);
        filterBox.addEventListener('change', applyFilters);
    </script>
</x-admin-layout>