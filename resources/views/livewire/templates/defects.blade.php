<div x-data="{ openAddDefect: false }" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($locked) opacity-50 cursor-not-allowed @endif">

     <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Defect</p>
    </div>
    <!-- ADD DEFECT BUTTON ABOVE TABLE -->
    <div class="w-full flex justify-center mb-3 px-3 mt-5 @if($systemname === 'ProcessRecord') hidden @endif ">
        <button
            @click="openAddDefect = true; $nextTick(() => $refs.firstInput?.focus())"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            @if($locked) disabled @endif id="add-defect">
            Add Defect
        </button>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    @if($systemname == 'ProcessRecord')
                    <th class="px-4 py-2">PPFNo</th>
                    @endif
                    <th class="px-6 py-2">Defect Type</th>
                    <th class="px-4 py-2">Quantity</th>
                    @if($systemname == 'ProcessRecord')
                    <th class="px-4 py-2">Endcode Date</th>
                    @endif
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse($defects as $defect)
                <tr wire:key="defect-{{ $defect['type'] }}">
                    @if($systemname == 'ProcessRecord')
                    <td class="px-4 py-2">{{ $defect['ppfno'] }}</td>
                    @endif

                    <td class="px-4 py-2">{{ $defect['type'] }}</td>
                    <td class="px-4 py-2">{{ $defect['qty'] > 0 ? $defect['qty'] : '' }}</td>
                    
                    @if($systemname == 'ProcessRecord')
                    <td class="px-4 py-2">{{ $defect['date'] }}</td>
                    @endif
                    
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmall: false, openEdit: false }">

                            <div class="flex gap-3">
                                <!-- ADD SMALL DEFECT -->
                                <button
                                    @click="openSmall = true"
                                    class="text-white bg-blue-700 px-4 py-2 rounded"
                                    wire:click="loadSmallDefects('{{ $defect['type'] }}')"
                                    @if($locked) disabled @endif>
                                    Add Small Defect
                                </button>

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

                            <!-- ADD SMALL DEFECT MODAL -->
                            <div x-show="openSmall" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display:none"></div>
                            <div x-show="openSmall" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display:none">
                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
                                    <h2 class="text-xl font-semibold mb-4 text-black">Add Small Defect for <span class="font-bold text-red-600 text-xl">{{ $defect['type'] }}</span></h2>
                                    <div class="flex flex-col justify-center gap-4 items-center">
                                        <div class="flex-col w-full">
                                            <label for="defectTypesmall" class="block text-sm font-medium text-black">Defect Type</label>
                                            <select id="defectTypesmall" class="mt-1 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                wire:model="newSmallDefect" required>
                                                <option>-- Select Small Defect --</option>
                                                @foreach ($SmallDefectsForModal ?? collect() as $s)
                                                <option value="{{ is_object($s) ? $s->SmallDefect : $s }}">{{ is_object($s) ? $s->SmallDefect : $s }}</option>
                                                @endforeach
                                            </select>
                                            @error('newSmallDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>

                                        <div class="flex-col w-full">
                                            <label for="qtySmall" class="block text-sm font-medium text-black">Quantity</label>
                                            <input type="number" id="qtySmall" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model.defer="newSmallQuan">
                                            @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>

                                        <button wire:click="addSmallDefect" @click="openSmall=false" class="w-full bg-green-700 text-white px-5 py-2.5 rounded-full hover:bg-green-800">Add Small Defect</button>
                                    </div>
                                    <button @click="openSmall = false" wire:click="$set('newSmallDefect', ''); $set('newSmallQuan', 0)" class="absolute top-3 right-3 text-gray-500 hover:text-black text-black">X</button>
                                </div>
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
                <tr class="bg-gray-500" wire:key="smalldefect-{{ $sDefect['type'] ?? $sDefect['small_defect']}}">
                    @if ($systemname == 'ProcessRecord')
                        <td class="px-8 py-1"></td>
                    <td class="px-4 py-1"></td>
                    @endif
                    <td class="px-8 py-1">{{ $sDefect['type'] ?? $sDefect['small_defect'] }}</td>
                    <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmallEdit: false }">
                            <button @click="openSmallEdit=true" class="text-white bg-green-700 px-4 py-2 rounded" wire:click="startEditSmall('{{ $sDefect['type'] ?? $sDefect['small_defect'] }}')" @if($locked) disabled @endif>Edit</button>
                            <button class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded" @click.prevent="if(confirm('Are you sure you want to delete this record?')) $wire.deleteDefectSmall(@js($sDefect['type'] ?? $sDefect['small_defect']))" @if($locked) disabled @endif>Delete</button>

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
                    <td colspan="{{ $systemname == 'ProcessRecord' ? 5 : 3 }}" class="px-6 py-4 text-center">
                        No defects added yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ERROR MESSAGES -->
    @error('newDefect') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    @error('newSmallDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

    <!-- ADD DEFECT MODAL -->
    <div x-show="openAddDefect" x-cloak x-trap.noscroll="openAddDefect" x-transition class="fixed inset-0 flex items-center justify-center z-50 p-4">
        <!-- Overlay -->
        <div @click="openAddDefect = false" x-show="openAddDefect" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40"></div>

        <!-- Modal Form -->
        <div class="relative bg-white rounded-lg shadow-sm w-full max-w-md z-50 p-6">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Add Defect</h3>
                <button type="button" @click="openAddDefect=false" class="text-gray-400 hover:text-gray-900 rounded-lg w-8 h-8 flex justify-center items-center">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                </button>
            </div>

            <div class="flex flex-col justify-center gap-4 mt-4 items-center">
                <!-- Defect Type -->
                <div class="flex flex-col w-full">
                    <label for="defectType" class="block text-sm font-medium text-black">Defect Type</label>
                    <select id="defectType" x-ref="firstInput" class="mt-1 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model="newDefect" required>
                        <option value="">-- Select Defect Type --</option>
                        @foreach ($Largedefects as $Ldefects)
                        <option value="{{ $Ldefects->LargeDefect }}">{{ $Ldefects->LargeDefect }}</option>
                        @endforeach
                    </select>
                    @error('newDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <!-- Quantity -->
                <div class="flex flex-col w-full">
                    <label for="qty" class="block text-sm font-medium text-black">Quantity</label>
                    <input type="number" id="qty" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black" wire:model="newQuan" required>
                    @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <!-- Add Button -->
                <button wire:click="addDefect" @click="openAddDefect = false" class="w-full px-6 py-3.5 text-white bg-[#0F3C89] hover:bg-blue-800 rounded-full text-sm">
                    Add Defect
                </button>
            </div>
        </div>
    </div>

</div>