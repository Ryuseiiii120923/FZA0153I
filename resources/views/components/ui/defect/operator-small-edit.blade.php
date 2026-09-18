@props([
    'defect' => [],
    'processKey' => '',
    'sDefect' => [],
    'readOnly' => false,
])

<div x-data="{ openSmallEdit: false }">
    <div class="flex gap-2">
        <button
            @click="openSmallEdit = true"
            class="text-white bg-green-700 px-4 py-2 rounded"
            wire:click="startEditSmallOperator('{{ $defect['type'] }}', '{{ $processKey }}', '{{ $defect['operatorid'] }}', '{{ $sDefect['type'] }}')"
            @if($readOnly) disabled @endif>
            Edit
        </button>

        <button
            class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
            @click.prevent="
                if (confirm('Are you sure you want to delete this record?')) {
                    $wire.deleteDefectSmallOperator(
                        @js($processKey),
                        @js($defect['operatorid']),
                        @js($sDefect['type'])
                    );
                }"
            @if($readOnly) disabled @endif>
            Delete
        </button>
    </div>

    <!-- BACKDROP -->
    <div x-show="openSmallEdit" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display: none"></div>

    <!-- MODAL -->
    <div x-show="openSmallEdit" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display: none">
        <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-black">
                Edit Small Defect for <span class="font-bold text-red-600 text-xl">{{ $sDefect['type'] }}</span>
            </h2>

            <div class="flex flex-col justify-center gap-4 items-center">
                <div class="flex-col w-full">
                    <label class="block text-sm font-medium text-black">Quantity</label>
                    <input type="number"
                        class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                        wire:model.defer="newSmallQuan">
                    @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <button
                    wire:click="updateDefectSmallArrayOperator('{{ $processKey }}')"
                    @click="$nextTick(() => openSmallEdit = false)"
                    @if($readOnly) disabled @endif
                    class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800 disabled:opacity-50">
                    Edit
                </button>
            </div>

            <button
                @click="openSmallEdit = false"
                wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', '')"
                class="absolute top-3 right-3 text-gray-500 hover:text-black">
                ✕
            </button>
        </div>
    </div>
</div>