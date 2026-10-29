<nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto overflow-x-hidden">
    <x-ui.navbar.nav-button
        page="dashboard"
        title="Dashboard"
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
    </x-ui.navbar.nav-button>

    <x-ui.navbar.nav-button
        page="enroll-operator"
        title="Enroll Operator"
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
                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
        </x-slot:icon>
    </x-ui.navbar.nav-button>

    <x-ui.navbar.nav-button
        page="PR"
        title="Generate Process Record"
        @click.stop="desktopExpanded = false; mobileOpen = false">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M12 2v3" />
                <path d="M12 19v3" />
                <path d="M2 12h3" />
                <path d="M19 12h3" />
                <path d="M4.9 4.9l2.1 2.1" />
                <path d="M17 17l2.1 2.1" />
                <path d="M4.9 19.1L7 17" />
                <path d="M17 7l2.1-2.1" />
                <circle cx="12" cy="12" r="4" />
                <path d="M10.5 12l1.2 1.2 2.3-2.4" />
            </svg>
        </x-slot:icon>
    </x-ui.navbar.nav-button>
</nav>