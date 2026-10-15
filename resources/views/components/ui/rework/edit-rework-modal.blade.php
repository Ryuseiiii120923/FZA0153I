@props(['rework', 'formId', 'readOnly'])

<div x-data="{ openSmall: false }" class="flex justify-center gap-2">
    <button
        @if($readOnly) disabled @endif
        @click="openSmall = true"
        wire:click="startEditRework('{{ $formId }}', '{{ $rework['type'] }}', '{{ $rework['hfno'] }}')"
        class="text-white bg-green-700 px-4 py-2 rounded">
        Edit
    </button>

    <button
        @if($readOnly) disabled @endif
        class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
        @click.prevent="
            if (confirm('Are you sure you want to delete this record?')) {
                $wire.deleteRework(@js($rework['hfno']), @js($rework['type']));
            }">
        Delete
    </button>

    <div x-show="openSmall" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display: none"></div>

    <div x-show="openSmall" x-transition
        class="fixed top-0 right-0 left-0 z-50 justify-center items-center flex w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
        style="display: none">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Edit Rework for
                        <span class="font-bold text-red-600 text-xl">{{ $rework['type'] }} (HFNO {{ $rework['hfno'] }})</span>
                    </h3>
                    <button
                        @if($readOnly) disabled @endif
                        type="button"
                        @click="openSmall = false"
                        wire:click="$set('newQuan', ''); $set('totalInsp.{{ $formId }}', '')"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col justify-center gap-4 my-4 items-center">
                    <div class="flex-col w-11/12 sm:w-1/3">
                        <label class="block text-sm font-medium text-black">Quantity</label>
                        <input type="number"
                            class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                            wire:model.defer="newQuan">
                        @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-col w-11/12 sm:w-1/3">
                        <label class="block text-sm font-medium text-black">Total Insp</label>
                        <input type="number"
                            class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                            wire:model.defer="totalInsp.{{ $formId }}">
                        @error('totalInsp.' . $formId) <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="w-full flex flex-col items-center mb-5">
                        <button
                            @if($readOnly) disabled @endif
                            wire:click="updateRework"
                            @click="$nextTick(() => openSmall = false)"
                            type="button"
                            class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm text-center">
                            Edit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>