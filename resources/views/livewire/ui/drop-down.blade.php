<div class="space-y-4">

    <!-- Add Operator Button -->
    <div class="px-2 py-5">
        <button
            wire:click="addNew"
            class="bg-blue-600 text-white px-4 py-2 rounded">
            + Add Operator
        </button>
    </div>

    @foreach($forms as $formId => $form)
    <div class="border rounded shadow p-3 relative" x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }">

        <!-- Header: Click to Toggle -->
        <div class="flex justify-between items-center cursor-pointer px-4 py-2 bg-gray-100"
            @click="open = !open; $wire.toggle('{{ $formId }}')">

            <span class="font-medium">Operator Form #{{ $form['hf_id'] ?? 'Unknown' }}</span>

            <div class="flex items-center gap-2">
                <!-- Remove Button -->
                <button
                    @click.stop
                    wire:click="remove('{{ $formId }}')"
                    class="rounded px-3 py-1 bg-red-600 text-white text-sm">
                    Remove
                </button>

                <!-- Arrow Icon -->
                <svg class="w-5 h-5 transform"
                    :class="{ 'rotate-180': open }"
                    fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown Content with Animation -->
        <div x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 max-h-0"
            x-transition:enter-end="opacity-100 max-h-screen"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 max-h-screen"
            x-transition:leave-end="opacity-0 max-h-0"
            class="overflow-hidden">

            <div class="flex flex-col gap-4 mt-4 p-4 bg-gray-50 rounded">

                <!-- HF ID + Total Inspect (Above) -->
               <div 
    x-data="{ open: @entangle('modalOpen') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
    @keydown.escape.prevent
    @click.away.prevent
>
    <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
        <h2 class="text-lg font-semibold mb-4">Operator HF ID</h2>

        <div class="flex flex-col gap-4">
            <div>
                <label class="block text-sm font-medium">HF ID</label>
                <input type="text"
                    wire:model="hf_id"
                    wire:blur="CheckHF{{ $this->currentFormId }}"
                    class="w-full border p-2 rounded"
                    placeholder="Enter HF ID"
                    maxlength="4" pattern="\d{4}">
                @error('hf_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium">Total Inspect</label>
                <input type="number"
                    wire:model="total_inspect"
                    class="w-full border p-2 rounded"
                    placeholder="Enter Total Inspect">
                @error('total_inspect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
            </div>

            <button wire:click="saveHF" class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                Save
            </button>
        </div>
    </div>
</div>

                <!-- Defects + Rework -->
                <div class="flex flex-col sm:flex-row gap-6 mt-4">
                    <div class="w-full sm:w-1/2 flex justify-center">
                        <livewire:templates.defects
                            :formId="$formId"
                            :loadedDefects="$form['defects']"
                            :loadedSmallDefects="$form['smallDefects']"
                            :key="'defects-'.$formId" />
                    </div>
                    <div class="w-full sm:w-1/2 flex justify-center">
                        <livewire:templates.rework
                            :formId="$formId"
                            :loadedRework="$form['rework']"
                            :key="'reworks-'.$formId" />
                    </div>
                </div>

            </div>
        </div>

    </div>
    @endforeach

    <div class="px-2 py-5">
        <button

            @if (!$toggles) hidden @endif
            wire:click="saveAll"
            class="bg-blue-600 text-white px-4 py-2 rounded">
            Save Operator
        </button>
    </div>

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
</div>

</div>