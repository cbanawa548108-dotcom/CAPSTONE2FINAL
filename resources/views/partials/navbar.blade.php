@php
    $navUser = auth()->user();
    $navInitials = collect(preg_split('/\s+/', trim($navUser->name ?? '')))->filter()->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'U';
    $navRole = ucfirst($navUser->role ?? 'user');
    $navAvatar = $navUser->avatarUrl();
@endphp
<header class="h-[68px] bg-white border-b border-[rgba(124,58,237,.07)] flex items-center justify-between px-6 flex-shrink-0 z-50 shadow-[0_1px_0_rgba(124,58,237,.06)]">

    {{-- Left: hamburger + search --}}
    <div class="flex items-center gap-4">
        <button @click="sidebarOpen = !sidebarOpen"
                class="p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-500 hover:text-violet-600 transition-all active:scale-95">
            <svg class="w-5 h-5 transition-transform duration-300" :class="sidebarOpen ? '' : 'rotate-90'"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        <div id="globalSearch" class="relative hidden md:flex items-center">
            <div class="flex w-64 items-center gap-2.5 rounded-2xl border-2 border-transparent bg-gray-100 px-4 py-2.5 transition-all duration-300 focus-within:w-80 focus-within:border-violet-400 focus-within:bg-white focus-within:shadow-md focus-within:shadow-violet-100">
                <svg class="h-4 w-4 flex-shrink-0 text-gray-400 transition-colors group-focus-within:text-violet-500"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input id="globalSearchInput" type="search" autocomplete="off" placeholder="Search fruits, transactions, reports…"
                       aria-label="Search fruits, transactions, and pages"
                       aria-controls="globalSearchResults" aria-expanded="false"
                       class="bg-transparent text-[13.5px] text-gray-700 outline-none w-full placeholder-gray-400">
                <kbd class="hidden lg:block text-[10px] text-gray-400 bg-gray-200 rounded-md px-1.5 py-0.5 font-mono flex-shrink-0">Ctrl K</kbd>
            </div>
            <div id="globalSearchResults" role="listbox" aria-label="Search results"
                 class="absolute left-0 top-14 z-[70] hidden w-[min(420px,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-violet-100 bg-white shadow-xl shadow-violet-900/10"></div>
        </div>
    </div>

    {{-- Right: actions --}}
    <div class="flex items-center gap-2" x-data="{ notif: false, profile: false, msgOpen: false }">

        {{-- Live Date --}}
        <div class="hidden xl:flex items-center gap-2 text-[12px] text-gray-500 bg-gray-100 rounded-xl px-3 py-2 font-medium select-none">
            <svg class="w-3.5 h-3.5 text-violet-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>{{ $prefs->formatDate(now()) }}</span>
        </div>

        {{-- Dark mode --}}
        <button @click="darkMode = !darkMode"
                class="p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all active:scale-95"
                :title="darkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
            <svg x-show="!darkMode" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
            <svg x-show="darkMode" class="w-5 h-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </button>

        {{-- Messages --}}
        <div class="relative">
            <button @click="msgOpen = !msgOpen; notif = false; profile = false"
                    @keydown.escape.window="msgOpen = false"
                    :aria-expanded="msgOpen.toString()"
                    aria-controls="navMessagePanel"
                    class="relative p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all active:scale-95"
                    title="Messages">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </button>

            <div id="navMessagePanel" x-show="msgOpen" @click.outside="msgOpen = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 top-14 w-[min(340px,calc(100vw-2rem))] card z-50 overflow-hidden"
                 x-cloak>
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-sm" style="font-family:Poppins,sans-serif">Messages</h3>
                    <button type="button" @click="msgOpen = false"
                            class="p-1 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                            aria-label="Close messages">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-8 text-center">
                    <div class="w-11 h-11 mx-auto mb-3 rounded-2xl bg-violet-50 text-violet-500 flex items-center justify-center">
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                    <p class="text-[13px] font-semibold text-gray-800">Messaging isn’t enabled yet</p>
                    <p class="text-[12px] text-gray-500 mt-1.5 leading-relaxed">Direct conversations aren’t available in this system, so there are no messages to show.</p>
                </div>
            </div>
        </div>

        {{-- Notifications --}}
        <div class="relative">
            <button @click="notif = !notif; profile = false; msgOpen = false"
                    @keydown.escape.window="notif = false"
                    :aria-expanded="notif.toString()"
                    aria-controls="navNotificationPanel"
                    class="relative p-2.5 rounded-xl hover:bg-[#F5F3FF] text-gray-400 hover:text-violet-600 transition-all active:scale-95"
                    title="Notifications" aria-label="Notifications">
                <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span id="navNotifDot" class="hidden absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white pulse-dot"></span>
            </button>

            <div id="navNotificationPanel" x-show="notif" @click.outside="notif = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 top-14 flex flex-col card z-50 overflow-hidden"
                 style="width:min(380px,calc(100vw - 1.5rem));max-height:calc(100vh - 88px)" x-cloak>

                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 bg-white">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 text-sm" style="font-family:Poppins,sans-serif">Notifications</h3>
                            <p class="text-[11px] text-gray-400">Stay up to date</p>
                        </div>
                        <span id="navNotifCount" class="badge badge-violet text-[10px] flex-shrink-0">0 new</span>
                    </div>
                    <button id="navMarkAll" type="button" hidden class="text-[11px] text-violet-600 font-semibold hover:text-violet-800 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-400 focus-visible:ring-offset-2 rounded flex-shrink-0 transition-colors">Mark all read</button>
                </div>

                <div id="navNotifList" class="overflow-y-auto divide-y divide-gray-100/80" style="max-height:min(440px,calc(100vh - 170px))">
                    <div class="px-5 py-8 text-center text-[12px] text-gray-400">Loading notifications…</div>
                </div>

                <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50/70 text-center">
                    <a href="{{ route('notifications') }}" class="inline-flex items-center gap-1 text-[12px] text-violet-600 font-semibold hover:text-violet-800 transition-colors">
                        View all notifications <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="w-px h-6 bg-gray-200 mx-1"></div>

        {{-- Profile --}}
        <div class="relative">
            <button @click="profile = !profile; notif = false; msgOpen = false"
                    class="flex items-center gap-2.5 pl-2 pr-3 py-1.5 rounded-2xl hover:bg-[#F5F3FF] transition-all active:scale-95 group">
                <div class="w-8 h-8 g-violet rounded-full flex items-center justify-center text-white text-xs font-black shadow-md shadow-violet-200 overflow-hidden">@if($navAvatar)<img src="{{ $navAvatar }}" alt="" class="w-full h-full object-cover">@else{{ $navInitials }}@endif</div>
                <div class="hidden lg:block text-left">
                    <p class="text-[12.5px] font-semibold text-gray-900 leading-tight">{{ $navUser->name }}</p>
                    <p class="text-[11px] text-violet-500 leading-tight">{{ $navRole }}</p>
                </div>
                <svg :class="profile ? 'rotate-180' : ''"
                     class="w-4 h-4 text-gray-400 transition-transform hidden lg:block"
                     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="profile" @click.outside="profile = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 top-14 w-60 card z-50 overflow-hidden" x-cloak>

                {{-- Profile header --}}
                <div class="p-4 bg-gradient-to-br from-[#F5F3FF] to-[#EDE9FE] border-b border-violet-100">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 g-violet rounded-2xl flex items-center justify-center text-white font-black text-sm shadow-lg shadow-violet-200 overflow-hidden">@if($navAvatar)<img src="{{ $navAvatar }}" alt="" class="w-full h-full object-cover">@else{{ $navInitials }}@endif</div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm" style="font-family:Poppins,sans-serif">{{ $navUser->name }}</p>
                            <p class="text-[11.5px] text-gray-500">{{ $navUser->email }}</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <svg class="w-3 h-3 text-violet-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 1a1 1 0 01.894.553l2.236 4.472 4.944.719a1 1 0 01.555 1.706l-3.576 3.486.844 4.92a1 1 0 01-1.45 1.054L10 15.347l-4.447 2.563a1 1 0 01-1.45-1.054l.844-4.92L1.371 8.45a1 1 0 01.555-1.706l4.944-.719L8.106 1.553A1 1 0 0110 1z" clip-rule="evenodd"/>
                                </svg>
                                <span class="badge badge-violet text-[10px] px-2 py-0.5">{{ $navRole }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Menu items --}}
                <div class="p-2">
                    @php
                    $profileMenu = [
                        ['settings',  'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'Account Settings', false],
                        ['users',     'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',                                                                                                                                                                                                                                                                                                                                                                                                                                                     'My Profile',       false],
                        ['notifications','M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',                                                                                                                                                                                                                                                                                                                    'Notifications',    false],
                    ];
                    @endphp
                    @foreach($profileMenu as [$r, $path, $label, $danger])
                    @continue($r === 'users' && ($navUser->role ?? null) !== 'owner')
                    <a href="{{ route($r) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-colors {{ $danger ? 'text-red-500 hover:bg-red-50 hover:text-red-600' : 'text-gray-600 hover:bg-[#F5F3FF] hover:text-violet-700' }}">
                        <svg class="w-4 h-4 flex-shrink-0 {{ $danger ? 'text-red-400' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/>
                        </svg>
                        {{ $label }}
                    </a>
                    @endforeach
                    <div class="my-1.5 border-t border-gray-100"></div>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('navLogoutForm').submit();"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium text-red-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                        <svg class="w-4 h-4 flex-shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<form id="navLogoutForm" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

<script>
(function () {
    const root = document.getElementById('globalSearch');
    const input = document.getElementById('globalSearchInput');
    const panel = document.getElementById('globalSearchResults');
    if (!root || !input || !panel) return;

    let timer;
    let controller;

    function closeResults() {
        panel.classList.add('hidden');
        input.setAttribute('aria-expanded', 'false');
    }

    function showMessage(message) {
        panel.replaceChildren();
        const status = document.createElement('p');
        status.className = 'px-4 py-4 text-sm text-gray-500';
        status.textContent = message;
        panel.append(status);
        panel.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    }

    function renderResults(results) {
        panel.replaceChildren();
        if (!results.length) {
            showMessage('No matching fruits, transactions, or pages.');
            return;
        }

        results.forEach(result => {
            const link = document.createElement('a');
            link.href = result.url;
            link.role = 'option';
            link.className = 'flex items-center gap-3 border-b border-gray-50 px-4 py-3 last:border-0 hover:bg-violet-50 focus:bg-violet-50 focus:outline-none';

            const type = document.createElement('span');
            type.className = 'w-16 flex-shrink-0 text-[10px] font-bold uppercase tracking-wide text-violet-600';
            type.textContent = result.type;

            const content = document.createElement('span');
            content.className = 'min-w-0 flex-1';

            const title = document.createElement('span');
            title.className = 'block truncate text-[13px] font-semibold text-gray-800';
            title.textContent = result.title;

            const detail = document.createElement('span');
            detail.className = 'mt-0.5 block truncate text-[11px] text-gray-500';
            detail.textContent = result.detail;

            content.append(title, detail);
            link.append(type, content);
            panel.append(link);
        });

        panel.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
    }

    async function search(query) {
        controller?.abort();
        if (query.length < 2) {
            closeResults();
            return;
        }

        controller = new AbortController();
        showMessage('Searching…');

        try {
            const params = new URLSearchParams({ q: query });
            const response = await fetch(`/api/search?${params}`, {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(`Search failed (HTTP ${response.status}).`);

            const data = await response.json();
            renderResults(data.results || []);
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Global search failed:', error);
            showMessage('Search is unavailable right now. Please try again.');
        }
    }

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        const query = input.value.trim();
        if (query.length < 2) {
            controller?.abort();
            closeResults();
            return;
        }
        timer = window.setTimeout(() => search(query), 250);
    });

    input.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeResults();
            input.blur();
        } else if (event.key === 'ArrowDown' && !panel.classList.contains('hidden')) {
            event.preventDefault();
            panel.querySelector('a')?.focus();
        }
    });

    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeResults();
            input.focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const links = [...panel.querySelectorAll('a')];
            const index = links.indexOf(document.activeElement);
            const next = event.key === 'ArrowDown'
                ? (index + 1) % links.length
                : (index - 1 + links.length) % links.length;
            event.preventDefault();
            links[next]?.focus();
        }
    });

    document.addEventListener('click', event => {
        if (!root.contains(event.target)) closeResults();
    });

    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            input.focus();
            input.select();
        }
    });
})();
</script>

<script>
// Navbar notifications (live from /api/notifications)
(function () {
    const list = document.getElementById('navNotifList');
    if (!list) return;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const pushOn = @json((bool) $prefs->push_notifications);
    const url = @json(route('notifications'));
    const warn = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z';
    const chart = 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z';
    const box = 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4';
    const coin = 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
    const style = {
        Critical: ['bg-red-100 text-red-600', warn],
        High:     ['bg-amber-100 text-amber-600', warn],
        Medium:   ['bg-violet-100 text-violet-600', chart],
        Low:      ['bg-green-100 text-green-600', box],
        Info:     ['bg-blue-100 text-blue-600', coin],
    };
    const esc = t => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const ago = iso => {
        const m = Math.max(0, Math.round((Date.now() - new Date(iso)) / 60000));
        if (m < 1) return 'just now';
        if (m < 60) return m + ' min ago';
        if (m < 1440) return Math.round(m / 60) + ' hr' + (Math.round(m / 60) > 1 ? 's' : '') + ' ago';
        return Math.round(m / 1440) + ' day' + (Math.round(m / 1440) > 1 ? 's' : '') + ' ago';
    };
    const post = path => fetch(path, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' } });

    function render(items) {
        const unread = items.filter(n => !n.is_read);
        const markAll = document.getElementById('navMarkAll');
        document.getElementById('navNotifCount').textContent = unread.length + ' new';
        markAll.hidden = unread.length === 0;
        document.getElementById('navNotifDot').classList.toggle('hidden', unread.length === 0 || !pushOn);
        const shown = [...unread, ...items.filter(n => n.is_read)].slice(0, 6);
        if (!shown.length) {
            list.innerHTML = `<div class="px-6 py-10 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <p class="text-[13px] font-semibold text-gray-700">You’re all caught up</p>
                <p class="text-[11px] text-gray-400 mt-1">New alerts will appear here.</p>
            </div>`;
            return;
        }
        list.innerHTML = shown.map(n => {
            const [icon, path] = style[n.severity] || style.Info;
            const message = n.message || n.subtitle || '';
            const urgent = n.severity === 'Critical';
            const statusBadge = urgent
                ? '<span class="notification-urgent-badge inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold flex-shrink-0">URGENT</span>'
                : n.is_read ? '' : '<span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[9px] font-bold text-violet-700 flex-shrink-0">NEW</span>';
            return `<button type="button" class="notification-row group relative flex w-full items-start gap-3 px-5 py-4 text-left transition-colors" data-id="${n.id}" data-link="${esc(n.action_link || '')}" aria-label="${urgent ? 'Urgent notification: ' : ''}${esc(n.title)}: ${esc(message)}">
                <span class="absolute left-0 top-3 bottom-3 w-1 rounded-r-full ${urgent ? 'notification-urgent-edge' : n.is_read ? 'bg-transparent' : 'bg-violet-500'}"></span>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${icon}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="${path}"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-[12.5px] font-semibold leading-5 ${urgent ? 'notification-urgent-title' : n.is_read ? 'text-gray-700' : 'text-gray-900'}">${esc(n.title)}</p>
                        ${statusBadge}
                    </div>
                    <p class="mt-1 text-[12px] text-gray-500 leading-[1.55]">${esc(message)}</p>
                    <p class="text-[11px] text-gray-400 mt-2 font-medium">${ago(n.created_at)}</p>
                </div>
                <svg class="w-4 h-4 mt-1 flex-shrink-0 text-gray-300 group-hover:text-violet-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>`;
        }).join('');
    }

    function load() {
        fetch('/api/notifications', { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(render)
            .catch(() => { list.innerHTML = '<div class="px-5 py-6 text-center text-[12px] text-gray-400">Could not load notifications</div>'; });
    }

    list.addEventListener('click', e => {
        const row = e.target.closest('[data-id]');
        if (!row) return;
        post('/api/notifications/' + row.dataset.id + '/read').finally(() => { window.location = row.dataset.link || url; });
    });
    document.getElementById('navMarkAll').addEventListener('click', () => post('/api/notifications/read-all').then(load));

    load();
    setInterval(load, 60000);
})();
</script>
