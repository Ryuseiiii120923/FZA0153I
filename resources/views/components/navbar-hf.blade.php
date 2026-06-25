<nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto overflow-x-hidden">

    <x-nav-button
        page="HFDashboard"
        title="Hand Finishing Dashboard"
        @click.stop="desktopExpanded = false; mobileOpen = false">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2">

                <path stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6" />
            </svg>
        </x-slot:icon>
    </x-nav-button>

    <x-nav-button
        page="HFDefectEncoding"
        title="Hand Finishing Defect Encoding"
        @click.stop="desktopExpanded = false; mobileOpen = false">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                {{-- Hand --}}
                <path d="M7 11V5a1 1 0 0 1 2 0v6" />
                <path d="M11 11V4a1 1 0 0 1 2 0v7" />
                <path d="M15 11V6a1 1 0 0 1 2 0v5" />
                <path d="M7 11c0-1.5 2-2 2 0v3" />
                <path d="M7 14c0 3 2 5 5 5s5-2 5-5" />
                {{-- Letter D badge (bottom-right) --}}
                <rect x="14" y="1" width="9" height="9" rx="2" fill="currentColor" stroke="none" />
                <text x="18.5" y="8" text-anchor="middle"
                    font-size="10" font-weight="bold"
                    fill="black" stroke="none"
                    font-family="Arial, sans-serif">D</text>
            </svg>
        </x-slot:icon>
    </x-nav-button>

    <x-nav-button
        page="HFReworkEncoding"
        title="Hand Finishing Rework Encoding"
        @click.stop="desktopExpanded = false; mobileOpen = false">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                {{-- Hand --}}
                <path d="M7 11V5a1 1 0 0 1 2 0v6" />
                <path d="M11 11V4a1 1 0 0 1 2 0v7" />
                <path d="M15 11V6a1 1 0 0 1 2 0v5" />
                <path d="M7 11c0-1.5 2-2 2 0v3" />
                <path d="M7 14c0 3 2 5 5 5s5-2 5-5" />
                {{-- Letter D badge (bottom-right) --}}
                <rect x="14" y="1" width="9" height="9" rx="2" fill="currentColor" stroke="none" />
                <text x="18.5" y="8" text-anchor="middle"
                    font-size="10" font-weight="bold"
                    fill="black" stroke="none"
                    font-family="Arial, sans-serif">R</text>
            </svg>
        </x-slot:icon>
    </x-nav-button>

</nav>