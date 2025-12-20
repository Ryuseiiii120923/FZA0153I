<div id="OuterPanel" class="outer-panel">
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
    <div class=" flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center">
        <div class="w-11/12 sm:w-1/3 mx-5 sm:mx-2">
            <label for="automach" class="block text-sm font-medium text-gray-700">
                Auto Machine
            </label>

            <select id="automach"
                class="mt-1 block w-full border border-black rounded-md px-2 py-1
        @if($locked) opacity-20 cursor-not-allowed @endif"
                @if($locked) disabled @endif
                wire:model="auto"
                required>
                <option value="">-- Select Auto Machine --</option>
                <option value="Vario 1">Vario 1</option>
                <option value="Vario 2">Vario 2</option>
                <option value="Vario 4">Vario 4</option>
                <option value="Assy 1">Assy 1</option>
                <option value="Assy 2">Assy 2</option>
            </select>
        </div>

        <div class="w-11/12 sm:w-1/3 mx-5 sm:mx-2">
            <label for="plant" class="block text-sm font-medium text-gray-700">
                Plant
            </label>

            <select id="plant"
                class="mt-1 block w-full border border-black rounded-md px-2 py-1
        @if($locked) opacity-20 cursor-not-allowed @endif"
                @if($locked) disabled @endif
                wire:model="plant"
                required>
                <option value="">-- Select Plant--</option>
                <option value="Plant 1A">Plant 1A</option>
                <option value="Plant 1B">Plant 1B</option>
                <option value="Plant 2A">Plant 2A</option>
                <option value="Plant 2B">Plant 2B</option>
            </select>
        </div>
    </div>

    <div class=" flex flex-col gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="inspectDate" class="block text-sm font-medium text-gray-700">Inspection Date</label>
            <input type="date" id="inspectDate" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " required  wire:model="InspectDates">
        </div>

        <div class="w-full">
            <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
            <input type="text" id="details" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="" required wire:model="details">
        </div>
    </div>

    <div class=" flex flex-col gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="upd" class="block text-sm font-medium text-gray-700">Update Date</label>
            <input type="date" id="upd" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="{{ now()->format('Y-m-d') }}" required readonly>
        </div>

        <div class="w-full">
            <label for="registrant" class="block text-sm font-medium text-gray-700">Registrant</label>
            <input type="text" id="registrant" class="text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required readonly wire:model="username">
        </div>
        <div class="flex items-center gap-2 justify-center p-6" id="buttons-action" wire:ignore>
            <button
                type="button"
                class="w-40 rounded-lg px-6 py-3.5 text-white font-medium text-sm px-5 py-2.5 text-center me-2 mb-2"
                wire:model="submitMethod"
                wire:click="submitAction"
                id="SubmitBtn"
                >
            </button>
        </div>

    </div>
</div>