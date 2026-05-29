<div>
    @if (session()->has('success'))
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
            <!-- Close Button -->
            <button
                @click="open = false"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <!-- Modal Content -->
            <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
            <p class="text-gray-700 mb-4">{{ session('success') }}</p>

            <button
                @click="open = false;
            location.reload();
            "
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
    @endif

    @if (session()->has('successAdd'))
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
            <!-- Close Button -->
            <button
                @click="open = false"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <!-- Modal Content -->
            <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
            <p class="text-gray-700 mb-4">{{ session('successAdd') }}</p>

            <button
                @click="open = false; window.location.href='{{ route('login') }}';"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
    @endif
    @if (session()->has('failed'))
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
            <!-- Close Button -->
            <button
                @click="open = false"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <!-- Modal Content -->
            <h2 class="text-lg font-semibold text-red-600 mb-2">Failed</h2>
            <p class="text-gray-700 mb-4">{{ session('failed') }}</p>

            <button
                @click="open = false;
            location.reload();
            "
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
    @endif
    @if (session()->has('error'))
    <div
        x-data="{ open: true }"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
        x-cloak>
        <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
            <!-- Close Button -->
            <button
                @click="open = false"
                class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                ✕
            </button>

            <!-- Modal Content -->
            <h2 class="text-lg font-semibold text-red-600 mb-2">Error</h2>
            <p class="text-gray-700 mb-4">{{ session('error') }}</p>

            <button
                @click="open = false;"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                OK
            </button>
        </div>
    </div>
    @endif



    <livewire:templates.checkppf
        systemname="ProcessRecord" />
    <div
        id="OuterPanel"
        x-data="{ locked: true, lockview: false }"
        x-on:lockbuttons.window="locked = true"
        x-on:removelock.window="locked = false"
        x-on:lockview.window="lockview = true"
        x-on:removelockview.window="lockview = false"
        :class="{
    'blur-sm pointer-events-none': locked,
    'pointer-events-none opacity-50 ': lockview
    }">
        <livewire:ui.drop-down />
        <div
            class="flex items-center gap-2 justify-center p-6"
            id="buttons-action"
            x-data="{ typingDisabled: false }"
            @remarks-typing.window="typingDisabled = $event.detail.disabled">
            <button
                :disabled="typingDisabled"
                @if (($hasAnyError ?? false))
                disabled
                @endif
                type="button"
                id="SubmitBtns"
                class="w-40 rounded-lg bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 text-white font-medium text-sm text-center me-2 mb-2 px-6 py-3.5 disabled:opacity-50 disabled:cursor-not-allowed @if($actiondash == 'View') hidden @endif"
                @class([ 'opacity-50 cursor-not-allowed'=> ($hasAnyError ?? false)
                ])
                @if($actiondash === 'edit')
                wire:click="editPrencode"
                @elseif($actiondash === 'delete')
                wire:click="deletePrencode"
                @else
                wire:click="addPrencode"
                @endif
                >
                @if ($actiondash === 'edit')
                Save
                @elseif ($actiondash === 'delete')
                Delete
                @else
                Add
                @endif
            </button>
        </div>
    </div>
    <div class="mt-3">
        <livewire:templates.operatordash :inspectorID="$inspectorID ?? null" />
    </div>
    <div
        wire:loading.flex
        wire:target="editPrencode"
        class="fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            ⏳ Saving Please wait...
        </div>
    </div>

    <div
        wire:loading.flex
        wire:target="addPrencode"
        class="fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            ⏳ Adding Please wait...
        </div>
    </div>

    <div
        wire:loading.flex
        wire:target="deletePrencode"
        class="fixed inset-0 z-50 items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            ⏳ Deleting Please wait...
        </div>
    </div>
</div>