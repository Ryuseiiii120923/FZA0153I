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
        :root {
            --sidebar-width: 64px;
        }
    </style>
</head>

<body class="overflow-x-hidden font-sans bg-gray-50">

    @if(request()->input('systemname') !== 'ProcessRecord')
    <div
        x-data="{
    mobileOpen: false,
    desktopExpanded: false,
    currentPage: 'dashboard',

    get isExpanded() {
        return this.desktopExpanded || this.mobileOpen;
    }
}"
        @keydown.escape.window="mobileOpen = false; desktopExpanded = false">
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
            <div
                class="hidden lg:flex items-center border-b border-white/10 overflow-hidden
                       hover:bg-white/10 transition-colors duration-150"
                style="height: 72px; min-height: 72px; padding: 0 12px;">
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
                <div class="lg:hidden flex-shrink-0 w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </div>
                <span
                    :class="isExpanded ? 'opacity-100 ml-3 max-w-full' : 'opacity-0 max-w-0 ml-0'"
                    class="text-sm font-semibold text-white whitespace-nowrap overflow-hidden transition-all duration-200">
                    VI Defect
                </span>

                <button
                    @click.stop="mobileOpen = false"
                    class="ml-auto text-white/60 hover:text-white lg:hidden flex-shrink-0"
                    aria-label="Close sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>


            <div class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-white/10">
                <span class="text-sm font-semibold text-white">VI Defect</span>
                <button @click="mobileOpen = false" class="text-white/60 hover:text-white" aria-label="Close">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>


            <x-navbar></x-navbar>
        </aside>


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


        @endif
        <header class="fixed top-0 right-0 z-20 bg-blue-600 shadow-md
                   left-0  @if(request()->input('systemname') !== 'ProcessRecord') lg:left-16 transition-[left] duration-300 @endif">
            <div class="w-full px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between" style="height: 72px;">


                <div class="w-10 lg:hidden flex-shrink-0"></div>


                <div class="hidden lg:flex flex-shrink-0 items-center">
                    <img src="{{ asset('images/fuji_logo.png') }}"
                        alt="Logo"
                        class="h-10 w-auto drop-shadow-lg" />
                </div>


                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-white text-center flex-1" id="title">
                    VI Defect
                </h1>

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


        <main @click="desktopExpanded = false; mobileOpen = false" class="pt-[72px] lg:pl-16 transition-[padding] duration-300 min-h-screen">
            <div class="px-4 sm:px-6 lg:px-8 py-6">

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
    </div>
    @livewireScripts
</body>

</html>