<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VI Defect</title>
    <link rel="icon" href="{{ asset('images/fuji_logo.ico') }}" type="image/x-icon">
    <script src="https://unpkg.com/@zxing/library@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
    @livewireStyles

    <style>
        /* Sidebar is always 64px wide (collapsed) on desktop.
           When expanded via Alpine, it becomes 224px.
           We sync the content offset using a CSS variable. */
        :root {
            --sidebar-width: 64px;
        }

        /* x-cloak hides elements until Alpine has initialized.
           Prevents flash of all panels on page load.
           Alpine removes [x-cloak] automatically once it boots. */
        /* wire:ignore wraps Alpine panels - Livewire must not touch them */
    </style>
</head>

<body class="overflow-x-hidden font-sans bg-gray-50">

    {{-- ============================================================ --}}
    {{-- SIDEBAR — always rendered, fixed left, sits under the header --}}
    {{-- ============================================================ --}}
    <div
        x-data="{
            mobileOpen: false,
            desktopExpanded: false,
            get isExpanded() { return this.desktopExpanded || this.mobileOpen; }
        }"
        @keydown.escape.window="mobileOpen = false; desktopExpanded = false">
        {{-- Mobile backdrop overlay --}}
        <div
            x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            style="display: none;"></div>

        {{-- Sidebar panel --}}
        <aside
            :class="{
                'w-56': isExpanded,
                'w-16': !isExpanded
            }"
            class="fixed left-0 z-40 flex flex-col
                   bg-[#0F3C89] text-white shadow-xl
                   transition-all duration-300 ease-in-out
                   -translate-x-full lg:translate-x-0
                   top-[72px] bottom-0 lg:top-0"
            :style="mobileOpen ? 'transform: translateX(0)' : null">
            {{-- Sidebar Header — click to toggle expand on desktop --}}
            <div
                class="hidden lg:flex items-center border-b border-white/10 overflow-hidden
                       hover:bg-white/10 transition-colors duration-150"
                style="height: 72px; min-height: 72px; padding: 0 12px;">
                {{-- Toggle button (desktop) --}}
                <button
                    type="button"
                    @click="desktopExpanded = !desktopExpanded"
                    class="hidden lg:flex flex-shrink-0 w-8 h-8 rounded-lg bg-white/20
                           items-center justify-center hover:bg-white/30 transition-colors duration-150"
                    :title="desktopExpanded ? 'Collapse sidebar' : 'Expand sidebar'"
                    aria-label="Toggle sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                {{-- App icon (mobile, non-clickable) --}}
                <div class="lg:hidden flex-shrink-0 w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </div>
                {{-- App name — shown when expanded --}}
                <span
                    :class="isExpanded ? 'opacity-100 ml-3 max-w-full' : 'opacity-0 max-w-0 ml-0'"
                    class="text-sm font-semibold text-white whitespace-nowrap overflow-hidden transition-all duration-200">
                    VI Defect
                </span>
                {{-- Mobile close X --}}
                <button
                    @click.stop="mobileOpen = false"
                    class="ml-auto text-white/60 hover:text-white lg:hidden flex-shrink-0"
                    aria-label="Close sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Mobile-only drawer header (app name + close) --}}
            <div class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-white/10">
                <span class="text-sm font-semibold text-white">VI Defect</span>
                <button @click="mobileOpen = false" class="text-white/60 hover:text-white" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Nav Items --}}
            <nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto overflow-x-hidden">

                {{-- Dashboard --}}
                <button
                    type="button"
                    @click="mobileOpen = false; $dispatch('navigate-to', { page: 'dashboard' })"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           text-white/70 hover:bg-white/10 hover:text-white
                           transition-colors duration-150"
                    title="Dashboard">
                    <span class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
                        </svg>
                    </span>
                    <span
                        :class="isExpanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'"
                        class="whitespace-nowrap overflow-hidden transition-all duration-200 text-left">
                        Dashboard
                    </span>
                </button>

                {{-- Enroll Operator --}}
                <button
                    type="button"
                    @click="mobileOpen = false; $dispatch('navigate-to', { page: 'enroll-operator' })"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           text-white/70 hover:bg-white/10 hover:text-white
                           transition-colors duration-150"
                    title="Enroll Operator">
                    <span class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </span>
                    <span
                        :class="isExpanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'"
                        class="whitespace-nowrap overflow-hidden transition-all duration-200 text-left">
                        Enroll Operator
                    </span>
                </button>

                <button
                    type="button"
                    @click="mobileOpen = false; $dispatch('navigate-to', { page: 'PR' })"
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           text-white/70 hover:bg-white/10 hover:text-white
                           transition-colors duration-150"
                    title="Generate Process Record">
                    <span class="flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round">

                            <path d="M3 21h18" />
                            <path d="M5 21V7l8 5V7l6 4v10" />
                        </svg>
                    </span>
                    <span
                        :class="isExpanded ? 'opacity-100 max-w-full' : 'opacity-0 max-w-0'"
                        class="whitespace-nowrap overflow-hidden transition-all duration-200 text-left">
                        Generate Process Record
                    </span>
                </button>
            </nav>
        </aside>

        {{-- Mobile burger button — only shows on small screens --}}
        <button
            type="button"
            @click="mobileOpen = true"
            class="lg:hidden fixed top-4 left-4 z-50
                   p-2 rounded-lg bg-[#0F3C89] text-white shadow-md
                   hover:bg-[#185FA5] focus:outline-none focus:ring-2 focus:ring-white"
            aria-label="Open sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    {{-- ============================================================ --}}
    {{-- HEADER — fixed top, starts after the sidebar on desktop      --}}
    {{-- ============================================================ --}}
    <header class="fixed top-0 right-0 z-20 bg-blue-600 shadow-md
                   left-0 lg:left-16 transition-[left] duration-300">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between" style="height: 72px;">

            {{-- Mobile spacer so title stays centered --}}
            <div class="w-10 lg:hidden flex-shrink-0"></div>

            {{-- Logo (desktop) --}}
            <div class="hidden lg:flex flex-shrink-0 items-center">
                <img src="{{ asset('images/fuji_logo.png') }}"
                    alt="Logo"
                    class="h-10 w-auto drop-shadow-lg" />
            </div>

            {{-- Title --}}
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-white text-center flex-1">
                VI Defect
            </h1>

            {{-- Logout --}}
            <div class="flex-shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-white hover:bg-blue-800 bg-[#0F3C89] font-medium rounded-lg text-sm px-4 py-2">
                        Logout
                    </button>
                </form>
            </div>

        </div>
    </header>

    {{-- ============================================================ --}}
    {{-- MAIN CONTENT — always offset 64px from left on desktop       --}}
    {{-- ============================================================ --}}
    <main class="pt-[72px] lg:pl-16 transition-[padding] duration-300 min-h-screen">
        <div class="px-4 sm:px-6 lg:px-8 py-6">

            {{-- Welcome --}}
            <h2 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800 text-center sm:text-left">
                Welcome, {{
                    Auth::user()?->employeeName?->名前
                    ?? Auth::guard('worker')->user()?->employee?->名前
                    ?? 'User'
                }}!
            </h2>

            @include('components.modals')

            <div class="w-full">
                {{ $slot }}
            </div>
        </div>
    </main>

    @livewireScripts
</body>

</html>