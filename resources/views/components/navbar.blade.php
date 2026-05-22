<nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto overflow-x-hidden">
    <x-nav-button
        page="dashboard"
        title="Dashboard">
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
        page="enroll-operator"
        title="Enroll Operator">
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
    </x-nav-button>

    <x-nav-button
        page="PR"
        title="Generate Process Record">
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

                <path d="M3 21h18" />
                <path d="M5 21V7l8 5V7l6 4v10" />
            </svg>
        </x-slot:icon>
    </x-nav-button>


    <x-nav-button
        page="HF"
        title="Hand Finsihing Dashboard">
        <x-slot:icon>
            <svg xmlns="http://www.w3.org/2000/svg"  width="24"
                height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M7 11V5a1 1 0 0 1 2 0v6" />
                <path d="M11 11V4a1 1 0 0 1 2 0v7" />
                <path d="M15 11V6a1 1 0 0 1 2 0v5" />
                <path d="M7 11c0-1.5 2-2 2 0v3" />
                <path d="M7 14c0 3 2 5 5 5s5-2 5-5" />
                <path d="M16 2l2 2 4-4" />
            </svg>
        </x-slot:icon>
    </x-nav-button>

</nav>