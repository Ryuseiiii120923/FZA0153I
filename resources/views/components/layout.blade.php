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

</head>

<body class="overflow-x-hidden font-sans">

    <header class="relative bg-blue-500 after:pointer-events-none after:absolute after:inset-x-0 after:inset-y-0 after:border-y after:border-white/10">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-4 sm:py-6 flex items-center justify-between">

            <!-- Logo (Far Left, Responsive) -->
            <div class="flex-shrink-0 flex items-center">
                <img src="{{ asset('images/fuji_logo.png') }}"
                    alt="Logo"
                    class="h-8 sm:h-10 md:h-12 lg:h-14 xl:h-16 2xl:h-20 w-auto drop-shadow-lg transition-all duration-300" />
            </div>

            <!-- Title -->
            <h1 id="title" class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight text-white text-center flex-1">
                VI Defect
            </h1>

            <!-- Logout (Far Right) -->
            <div class="flex-shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-white hover:bg-blue-700 bg-[#0F3C89] font-medium rounded-lg text-sm sm:text-base px-4 sm:px-5 py-2 sm:py-2.5">
                        Logout
                    </button>
                </form>
            </div>

        </div>
    </header>

    <main>
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-6 text-gray-800 text-center sm:text-left">
                Welcome, {{
                Auth::user()?->employeeName?->名前
                ?? Auth::guard('worker')->user()?->employee?->名前
                ?? 'User'
                }}!
            </h1>

            @include('components.modals')

            <div class="w-full">
                {{ $slot }}
            </div>
        </div>
    </main>
    @livewireScripts
</body>

</html>