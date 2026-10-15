<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>VI Defect</title>

    <link rel="icon"
        href="{{ asset('images/fuji_logo.ico') }}"
        type="image/x-icon">

    <script src="{{ asset('js/flowbite.min.js') }}"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles

    <style>
        :root {
            --sidebar-width: 64px;
        }
    </style>
</head>

<body class="overflow-x-hidden font-sans bg-gray-50">

    @if(request()->input('systemname') !== 'ProcessRecord')

    {{-- =========================================================
            SIDEBAR ALPINE CONTAINER
        ========================================================== --}}
    <div
        x-data="{
        mobileOpen: false,
        desktopExpanded: false,
        currentPage: 'dashboard',

        get isExpanded() {
            return this.desktopExpanded || this.mobileOpen;
        },

        closeMobile() {
            this.mobileOpen = false;
        },

        toggleDesktop() {
            this.desktopExpanded = !this.desktopExpanded;
        }
    }"
        @keydown.escape.window="mobileOpen = false; desktopExpanded = false">

        {{-- =====================================================
                MOBILE OVERLAY
            ====================================================== --}}
        <div
            x-show="mobileOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileOpen = false"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            style="display: none;"></div>


        {{-- =====================================================
                SIDEBAR
            ====================================================== --}}
        <aside
            class="fixed left-0 top-0 bottom-0 z-40
                       flex flex-col
                       bg-[#0F3C89] text-white shadow-xl
                       transition-all duration-300 ease-in-out
                       -translate-x-full
                       lg:translate-x-0"
            :class="{
                    'translate-x-0': mobileOpen,

                    'lg:w-56': desktopExpanded,
                    'lg:w-16': !desktopExpanded
                }">

            {{-- =================================================
                    DESKTOP SIDEBAR HEADER
                ================================================== --}}
            <div
                class="hidden lg:flex items-center
                           border-b border-white/10
                           overflow-hidden
                           hover:bg-white/10
                           transition-colors duration-150"
                style="height: 72px; min-height: 72px; padding: 0 12px;">

                {{-- Desktop hamburger --}}
                <button
                    type="button"
                    @click="toggleDesktop()"
                    class="flex flex-shrink-0
                               w-8 h-8
                               rounded-lg
                               bg-white/20
                               items-center justify-center
                               hover:bg-white/30
                               transition-colors duration-150"
                    :title="desktopExpanded ? 'Collapse sidebar' : 'Expand sidebar'"
                    aria-label="Toggle sidebar">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>

                </button>


                {{-- Desktop title --}}
                <span
                    x-show="desktopExpanded"
                    x-transition
                    class="ml-3
                               text-sm font-semibold
                               text-white
                               whitespace-nowrap
                               overflow-hidden">
                    @if(request()->input('process') === 'HF')
                    HF Defect
                    @else
                    VI Defect
                    @endif
                </span>

            </div>


            {{-- =================================================
                    MOBILE SIDEBAR HEADER
                ================================================== --}}
            <div
                class="lg:hidden
                           flex items-center
                           justify-between
                           px-4 py-4
                           border-b border-white/10">

                <span class="text-sm font-semibold text-white">

                    @if(request()->input('process') === 'HF')
                    HF Defect
                    @else
                    VI Defect
                    @endif

                </span>


                {{-- Mobile close button --}}
                <button
                    type="button"
                    @click="mobileOpen = false"
                    class="text-white/70
                               hover:text-white
                               transition-colors"
                    aria-label="Close sidebar">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>

                </button>

            </div>


            {{-- =================================================
                    NAVIGATION
                ================================================== --}}
            <div class="flex-1 overflow-y-auto">
                <x-ui.navbar.navbar />
            </div>

        </aside>


        {{-- =====================================================
    MOBILE HAMBURGER BUTTON
====================================================== --}}
        <button
            type="button"
            x-show="!mobileOpen"
            x-transition.opacity.duration.150ms
            @click.stop="mobileOpen = true"
            class="lg:hidden
           fixed
           top-4
           left-4
           z-[60]
           p-2
           rounded-lg
           bg-[#0F3C89]
           text-white
           shadow-md
           hover:bg-[#185FA5]
           focus:outline-none
           focus:ring-2
           focus:ring-white"
            aria-label="Open sidebar">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    @endif


    {{-- =============================================================
        HEADER
    ============================================================= --}}
    <header
        class="fixed
               top-0
               right-0
               z-20
               bg-blue-600
               shadow-md
               left-0

               @if(request()->input('systemname') !== 'ProcessRecord')
                   lg:left-16
                   transition-[left]
                   duration-300
               @endif">

        <div
            class="w-full
                   px-4
                   sm:px-6
                   lg:px-8
                   py-4
                   flex
                   items-center
                   justify-between"
            style="height: 72px;">

            {{-- =====================================================
                MOBILE LEFT SPACER
            ====================================================== --}}
            <div class="w-10 lg:hidden flex-shrink-0"></div>


            {{-- =====================================================
                FUJI LOGO
            ====================================================== --}}
            <div class="hidden lg:flex flex-shrink-0 items-center">

                <img
                    src="{{ asset('images/fuji_logo.png') }}"
                    alt="Logo"
                    class="h-10 w-auto drop-shadow-lg" />

            </div>


            {{-- =====================================================
                PAGE TITLE
            ====================================================== --}}
            <h1
                class="text-xl
                       sm:text-2xl
                       md:text-3xl
                       font-bold
                       tracking-tight
                       text-white
                       text-center
                       flex-1"
                id="title">

                {{ request()->input('process') === 'HF'
                    ? 'HF Defect'
                    : 'VI Defect'
                }}

            </h1>


            {{-- =====================================================
                LOGOUT
            ====================================================== --}}
            <div class="flex-shrink-0">

                <form
                    method="POST"
                    action="{{ route('logout') }}">

                    @csrf

                    <button
                        type="submit"
                        class="text-white
                               hover:bg-blue-800
                               bg-[#0F3C89]
                               font-medium
                               rounded-lg
                               text-sm
                               px-4
                               py-2">
                        Logout
                    </button>

                </form>

            </div>

        </div>

    </header>


    {{-- =============================================================
        MAIN CONTENT
    ============================================================= --}}
    <main
        class="pt-[72px]
               lg:pl-16
               min-h-screen
               transition-[padding]
               duration-300">

        <div
            class="px-4
                   sm:px-6
                   lg:px-8
                   py-6">

            {{-- =====================================================
                WELCOME
            ====================================================== --}}
            <h2
                class="text-xl
                       sm:text-2xl
                       font-bold
                       mb-6
                       text-gray-800
                       text-center
                       sm:text-left">

                Welcome,
                {{
                    Auth::user()?->employeeName?->名前
                    ?? Auth::guard('worker')->user()?->employee?->名前
                    ?? 'User'
                }}!

            </h2>


            {{-- =====================================================
                LIVEWIRE CONTENT
            ====================================================== --}}
            <div class="w-full">

                {{ $slot }}

            </div>

        </div>

    </main>


    @livewireScripts

</body>

</html>