<div x-data="{ openAddDefect: false }" class="bg-white rounded-lg w-full max-w-2xl mx-auto p-4 @if($locked) opacity-50 cursor-not-allowed @endif">

    <p class="text-4xl font-extrabold bg-gray-700 w-full text-center text-white p-4">Defect</p>

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Operator ID</th>
                    <th class="px-6 py-2">Defect Type</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Endcode Date</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse($defects as $defect)
                <tr wire:key="defect-{{ $defect['type'] }}">
                    <td class="px-4 py-2">{{ $defect['operatorid'] }}</td>
                    <td class="px-4 py-2">{{ $defect['type'] }}</td>
                    <td class="px-4 py-2">{{ $defect['qty'] > 0 ? $defect['qty'] : '' }}</td>
                    <td class="px-4 py-2">{{ $defect['dateEncode'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmall: false, openEdit: false }">

                            <div class="flex gap-3">
                                <!-- EDIT DEFECT -->
                                <button
                                    @click="openEdit = true"
                                    class="text-white bg-green-700 px-4 py-2 rounded"
                                    wire:click="startEdit('{{ $defect['type'] }}')"
                                    @if($locked) disabled @endif>
                                    Edit
                                </button>

                                <!-- DELETE DEFECT -->
                                <button
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                    @click.prevent="if (confirm('Are you sure you want to delete this record?')) $wire.deleteDefectArray(@js($defect['type']))"
                                    @if($locked) disabled @endif>
                                    Delete
                                </button>
                            </div>

                            <!-- EDIT DEFECT MODAL -->
                            <div x-show="openEdit" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display:none"></div>
                            <div x-show="openEdit" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display:none">
                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
                                    <h2 class="text-xl font-semibold mb-4 text-black">Edit Defect for <span class="font-bold text-red-600 text-xl">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col justify-center gap-4 items-center">
                                        <div class="flex-col w-full">
                                            <label class="block text-sm font-medium text-black">Quantity</label>
                                            <input type="number" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model.defer="newQuan">
                                            @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>
                                        <button wire:click="updateDefectArray" @click="$nextTick(() => openEdit=false)" class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">Edit</button>
                                    </div>
                                    <button @click="openEdit=false" wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', 0)" class="absolute top-3 right-3 text-gray-500 hover:text-black text-black">X</button>
                                </div>
                            </div>

                        </div>
                    </td>

                </tr>

                <!-- SMALL DEFECT ROWS -->
                @if(isset($smallDefects[$defect['type']]))
                @foreach($smallDefects[$defect['type']] as $sDefect)
                <tr class="bg-gray-500" wire:key="smalldefect-{{ $sDefect['type']}}">
                    <td class="px-8 py-1"></td>
                    <td class="px-8 py-1">{{ $sDefect['type'] }}</td>
                    <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                    <td class="px-8 py-1"></td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmallEdit: false }">
                            <button @click="openSmallEdit=true" class="text-white bg-green-700 px-4 py-2 rounded" wire:click="startEditSmall('{{ $sDefect['type'] }}')" @if($locked) disabled @endif>Edit</button>
                            <button class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded" @click.prevent="if(confirm('Are you sure you want to delete this record?')) $wire.deleteDefectSmall(@js($sDefect['type']))" @if($locked) disabled @endif>Delete</button>

                            <div x-show="openSmallEdit" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display:none"></div>
                            <div x-show="openSmallEdit" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display:none">
                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
                                    <h2 class="text-xl font-semibold mb-4 text-black">Edit Small Defect for <span class="font-bold text-red-600 text-xl">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col justify-center gap-4 items-center">
                                        <div class="flex-col w-full">
                                            <label class="block text-sm font-medium text-black">Quantity</label>
                                            <input type="number" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model.defer="newSmallQuan">
                                            @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>
                                        <button wire:click="updateDefectSmallArray" @click="$nextTick(() => openSmallEdit=false)" class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">Edit</button>
                                    </div>
                                    <button @click="openSmallEdit=false" wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', 0)" class="absolute top-3 right-3 text-gray-500 hover:text-black text-black">X</button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
                @endif

                @empty
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center">
                        No defects added yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
            <label for="TotalNG" class="block text-sm font-medium text-black">Total NG</label>
            <input type="text" id="TotalNG" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " required wire:model="TotalNg" readonly>
        </div>
    </div>

    <!-- ERROR MESSAGES -->
    @error('newDefect') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    @error('newSmallDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
</div>