<div class="space-y-4">

    <!-- Add Operator Button -->
    <div class="px-2 py-5">
        <button
            wire:click="addNew"
            class="bg-blue-600 text-white px-4 py-2 rounded">
            + Add Worker
        </button>
    </div>

    @foreach($forms as $formId => $form)
   <div 
    wire:key="worker-form-{{ $formId }}"
    class="border rounded shadow p-3 relative"
    x-data="{ open: {{ $form['open'] ? 'true' : 'false' }} }">

        <!-- Header: Click to Toggle -->
        <div class="flex justify-between items-center cursor-pointer px-4 py-2 bg-gray-100"
            @click="open = !open; $wire.toggle('{{ $formId }}')">

            <span class="font-medium">Worker Form #{{ $form['hf_id'] ?? 'Unknown' }}</span>

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

                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="w-full sm:w-1/2">
                        <label class="block text-sm font-medium">HF ID</label>
                        <div class="flex items-center gap-3">
                            <input type="text" wire:model="forms.{{ $formId }}.hf_id" class="w-full border bg-gray-500 p-2 rounded" readonly placeholder="Enter HF ID" maxlength="4" pattern="\d{4}"> 
                            @if(!empty($form['hf_name'])) <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p> @endif
                        </div>
                    </div>
                    @error('forms.' . $formId . '.hf_id')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                    <div class="w-full sm:w-1/2">
                        <label class="block text-sm font-medium">Total Inspect</label>
                        <input type="number" readonly wire:model="forms.{{ $formId }}.total_inspect" class="w-full border bg-gray-500 p-2 rounded" placeholder="Enter Total Inspect">
                    </div>
                </div>
                    <!-- HF ID + Total Inspect (Above) -->
                    <div
                        x-data="{ open: @entangle('modalOpen.' . $formId) }"
                        x-show="open"
                        x-cloak
                        class="fixed inset-0 flex items-center justify-center bg-black/50 z-50"
                        @keydown.escape.window="open = false">
                        <div class="bg-white rounded-lg p-6 w-11/12 sm:w-1/3">
                            <h2 class="text-lg font-semibold mb-4">Operator HF ID</h2>
                            <div class="flex flex-col gap-4">
                                <div>
                                    <label class="block text-sm font-medium">HF ID</label>
                                    @if(!empty($form['hf_name'])) <p class="text-sm font-medium text-black">{{ $form['hf_name'] }}</p> @endif
                                    <input type="text"
                                        wire:model.lazy="forms.{{ $formId }}.hf_id"
                                        wire:blur="CheckHf('{{ $formId }}')"
                                        class="w-full border p-2 rounded"
                                        placeholder="Enter HF ID"
                                        maxlength="4" pattern="\d{4}">

                                    @error('forms.' . $formId . '.hf_id') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium">Total Inspect</label>
                                    <input type="number"
                                        wire:model="forms.{{ $formId }}.total_inspect"
                                        class="w-full border p-2 rounded"
                                        placeholder="Enter Total Inspect">
                                    @error('forms.' . $formId . '.total_inspect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                </div>

                                <button wire:click="saveHF('{{ $formId }}')" @if($hasError) disabled @endif class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                                    Save
                                </button>
                                
                                <button wire:click="exitHF('{{ $formId }}')" @if($hasError) disabled @endif class="bg-green-600 text-white px-4 py-2 rounded mt-2 w-full">
                                    Exit
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