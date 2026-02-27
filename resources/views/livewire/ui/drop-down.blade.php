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

            <span class="font-medium">Operator Form #{{ $loop->iteration }}</span>

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
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="w-full sm:w-1/2">
                        <label class="block text-sm font-medium">HF ID</label>
                        <input type="text"
                               wire:model="forms.{{ $formId }}.hf_id"
                               class="w-full border p-2 rounded"
                               placeholder="Enter HF ID">
                    </div>

                    <div class="w-full sm:w-1/2">
                        <label class="block text-sm font-medium">Total Inspect</label>
                        <input type="number"
                               wire:model="forms.{{ $formId }}.total_inspect"
                               class="w-full border p-2 rounded"
                               placeholder="Enter Total Inspect">
                    </div>
                </div>

                <!-- Defects + Rework -->
                <div class="flex flex-col sm:flex-row gap-6 mt-4">
                    <div class="w-full sm:w-1/2 flex justify-center">
                        <livewire:templates.defects :formId="$formId" :key="'defects-'.$formId" />
                    </div>
                    <div class="w-full sm:w-1/2 flex justify-center">
                        <livewire:templates.rework :formId="$formId" :key="'rework-'.$formId" />
                    </div>
                </div>

            </div>
        </div>

    </div>
    @endforeach

</div>