<div x-data="{ openDefect: false }" x-cloak class="bg-white rounded-lg w-full max-w-md mx-auto" id="OuterPanel">
    <p class="text-4xl font-extrabold bg-gray-700 w-full text-center text-white p-4">Defect</p>
    <div class="w-full flex flex-col items-center mt-5">
        <button @click="openDefect = true"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
            Add Defect
        </button>
    </div>
    <div class="overflow-x-auto mt-3 mx-3">
        <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Defect Type</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse ( $defects as $defect )
                <tr wire:key="defect-{{ $defect['type'] }}">
                    <td class="px-4 py-2">{{ $defect['type'] }}</td>
                    <td class="px-4 py-2">{{ (int)$defect['qty'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">

                        <div x-data="{ openSmall: false, openEdit: false }">

                            <div class="flex gap-3">
                                <button
                                    @click="openSmall = true"
                                    class="text-white bg-blue-700 px-4 py-2 rounded"
                                    wire:click="loadSmallDefects('{{ $defect['type'] }}')">
                                    Add Small Defect
                                </button>

                                <button
                                    @click="openEdit = true"
                                    class="text-white bg-green-700 px-4 py-2 rounded"
                                    wire:click="startEdit('{{ $defect['type'] }}')">
                                    Edit
                                </button>

                                <button

                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                    @click.prevent="
            if (confirm('Are you sure you want to delete this record?')) {
                $wire.deleteDefectArray(@js($defect['type']));
            }
        ">
                                    Delete
                                </button>

                            </div>

                            <div
                                x-show="openSmall"
                                x-transition.opacity
                                class="fixed inset-0 bg-black bg-opacity-40 z-40"
                                style="display: none">
                            </div>


                            <div
                                x-show="openSmall"
                                x-transition
                                class="fixed inset-0 flex items-center justify-center z-50"
                                style="display: none">

                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">

                                    <h2 class="text-xl font-semibold mb-4 text-black">Add Small Defect for <span class="font-bold text-red-600 text-xl">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col justify-center gap-4 items-center">


                                        <div class="flex-col w-full">
                                            <label for="defectTypesmall" class="block text-sm font-medium text-black">
                                                Defect Type
                                            </label>

                                            <select id="defectTypesmall" class="mt-1 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                wire:model="newSmallDefect"
                                                required>
                                                <option> -- Select Small Defect --</option>
                                                @foreach ($SmallDefectsForModal ?? collect() as $s)
                                                <option value="{{ is_object($s) ? $s->SmallDefect : $s }}">{{ is_object($s) ? $s->SmallDefect : $s }}</option>
                                                @endforeach
                                            </select>

                                            @error('newSmallDefect')
                                            <p class="text-red-500 text-sm">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- QUANTITY -->
                                        <div class="flex-col w-full">
                                            <label for="qtySmall" class="block text-sm font-medium text-black">
                                                Quantity
                                            </label>

                                            <input
                                                type="text"
                                                id="qtySmall"
                                                class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                wire:model.defer="newSmallQuan">

                                            @error('newSmallQuan')
                                            <p class="text-red-500 text-sm">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- SUBMIT BUTTON -->
                                        <button
                                            @click="openSmall = false"
                                            wire:click="addSmallDefect"
                                            type="button"
                                            class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">
                                            Add Small Defect
                                        </button>
                                    </div>

                                    <!-- CLOSE BUTTON -->
                                    <button
                                        @click="openSmall = false" id="close-button-small"
                                        wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', 0)"
                                        class="absolute top-3 right-3 text-gray-500 hover:text-black text-black">
                                        X
                                    </button>

                                </div>
                            </div>

                            <div
                                x-show="openEdit"
                                x-transition.opacity
                                class="fixed inset-0 bg-black bg-opacity-40 z-40"
                                style="display: none">
                            </div>


                            <div
                                x-show="openEdit"
                                x-transition
                                class="fixed inset-0 flex items-center justify-center z-50"
                                style="display: none">

                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">

                                    <h2 class="text-xl font-semibold mb-4 text-black">Edit Defect for <span class="font-bold text-red-600 text-xl">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col justify-center gap-4 items-center">

                                        <!-- QUANTITY -->
                                        <div class="flex-col w-full">
                                            <label for="qtySmall" class="block text-sm font-medium text-black">
                                                Quantity
                                            </label>

                                            <input
                                                type="text"
                                                id="qtySmall"
                                                class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                wire:model.defer="newQuan">

                                            @error('newQuan')
                                            <p class="text-red-500 text-sm">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- SUBMIT BUTTON -->
                                        <button
                                            wire:click="updateDefectArray"
                                            @click="$nextTick(() => openEdit = false)"
                                            type="button"
                                            class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">
                                            Edit
                                        </button>
                                    </div>

                                    <!-- CLOSE BUTTON -->
                                    <button
                                        @click="openEdit = false" id="close-button-edit"
                                        wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', 0)"
                                        class="absolute top-3 right-3 text-gray-500 hover:text-black text-black">
                                        X
                                    </button>

                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @if(isset($smallDefects[$defect['type']]))
                @foreach($smallDefects[$defect['type']] as $sDefect)
                <tr class="bg-gray-500" wire:key="smalldefect-{{ $sDefect['type']}}">
                    <td class="px-8 py-1">{{ $sDefect['type'] }}</td>
                    <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">

                        <div x-data="{ openSmallEdit: false }">
                            <button

                                @click="openSmallEdit = true"
                                class="text-white bg-green-700 px-4 py-2 rounded"
                                wire:click="startEditSmall('{{ $sDefect['type'] }}')">
                                Edit
                            </button>

                            <button

                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                @click.prevent="if (confirm('Are you sure you want to delete this record?')) {
                $wire.deleteDefectArray(@js($sDefect['type']));
            }
        ">
                                Delete
                            </button>


                            <div
                                x-show="openSmallEdit"
                                x-transition.opacity
                                class="fixed inset-0 bg-black bg-opacity-40 z-40"
                                style="display: none">
                            </div>


                            <div
                                x-show="openSmallEdit"
                                x-transition
                                class="fixed inset-0 flex items-center justify-center z-50"
                                style="display: none">

                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">

                                    <h2 class="text-xl font-semibold mb-4 text-black">Add Small Defect for <span class="font-bold text-red-600 text-xl">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col justify-center gap-4 items-center">

                                        <!-- QUANTITY -->
                                        <div class="flex-col w-full">
                                            <label for="qtySmall" class="block text-sm font-medium text-black">
                                                Quantity
                                            </label>

                                            <input
                                                type="text"
                                                id="qtySmall"
                                                class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                wire:model.defer="newSmallQuan">

                                            @error('newSmallQuan')
                                            <p class="text-red-500 text-sm">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- SUBMIT BUTTON -->
                                        <button
                                            wire:click="updateDefectSmallArray"
                                            @click="$nextTick(() => openSmallEdit = false)"
                                            type="button"
                                            class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">
                                            Edit
                                        </button>
                                    </div>

                                    <!-- CLOSE BUTTON -->
                                    <button
                                        @click="openSmallEdit = false" id="close-button-smallEdit"
                                        wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', 0)"
                                        class="absolute top-3 right-3 text-gray-500 hover:text-black text-black">
                                        X
                                    </button>

                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
                @endif
                @empty
                <tr>
                    <td colspan="2" class="px-4 py-2 text-center">No defects added yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @error('newDefect')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('newQuan')
    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
    @error('newSmallQuan')
    <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror
    @error('newSmallDefect')
    <p class="text-red-500 text-sm">{{ $message }}</p>
    @enderror

    <div x-show="openDefect" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-gray-900/50 z-40" @click="openDefect = false"></div>

        <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-2xl z-50">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="text-xl font-semibold text-gray-900">Add Defect</h3>
                <button @click="openDefect = false;"
                    class="text-gray-400 hover:text-gray-900">X</button>
            </div>

            <div class="flex flex-col justify-center gap-4 my-4 items-center">
                <div class="w-11/12 sm:w-1/3">
                    <label for="defectType" class="block text-sm font-medium text-black">Defect Type</label>
                    <select id="defectType" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        wire:model="newDefect" required>
                        <option value="">-- Select Defect Type --</option>
                        @foreach ($Largedefects as $Ldefects)
                        <option value="{{ $Ldefects->LargeDefect }}">{{ $Ldefects->LargeDefect }}</option>
                        @endforeach
                    </select>
                    @error('newDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="w-11/12 sm:w-1/3">
                    <label for="qty" class="block text-sm font-medium text-black">Quantity</label>
                    <input type="text" id="qty" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        wire:model="newQuan" required>
                    @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <button wire:click="addDefect" 
                @click="openDefect = false;"
                    class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-[#0F3C89] hover:bg-blue-800 rounded-full">
                    Add Defect
                </button>
            </div>
        </div>
    </div>
</div>