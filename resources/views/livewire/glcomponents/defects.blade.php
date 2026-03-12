<div class="bg-white rounded-lg w-full max-w-1xl py-4 @if($locked) opacity-50 cursor-not-allowed @endif">

     <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Defect</p>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto mt-3">
        <table class="table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">Inspector ID</th>
                    <th class="px-4 py-2">Inspector Name</th>
                    <th class="px-6 py-2">Defect Type</th>
                    <th class="px-4 py-2">Quantity</th>
                    <th class="px-4 py-2">Endcode Date</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse($defects as $defect)
                <tr wire:key="defect-{{ $defect['type'] }}">
                    <td class="px-4 py-2">{{ $defect['operatorid'] }}</td>
                    <td class="px-4 py-2">{{ $defect['operatorname'] }}</td>
                    <td class="px-4 py-2">{{ $defect['type'] }}</td>
                    <td class="px-4 py-2">{{ $defect['qty'] > 0 ? $defect['qty'] : '' }}</td>
                    <td class="px-4 py-2">{{ $defect['dateEncode'] }}</td>
                </tr>


                <!-- SMALL DEFECT ROWS -->
                @if(isset($smallDefects[$defect['type']][$defect['operatorid']]))
                @foreach($smallDefects[$defect['type']][$defect['operatorid']] as $sDefect)
                <tr class="bg-gray-500" wire:key="smalldefect-{{ $sDefect['type']}}">
                    <td class="px-8 py-1"></td>
                     <td class="px-8 py-1"></td>
                    <td class="px-9 py-1">- {{ $sDefect['type'] }}</td>
                    <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmallEdit: false }">
                            <div x-show="openSmallEdit" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40" style="display:none"></div>
                            <div x-show="openSmallEdit" x-transition class="fixed inset-0 flex items-center justify-center z-50" style="display:none">
                                <div class="relative bg-white rounded-lg shadow p-6 w-full max-w-md">
                                    <h2 class="text-xl font-semibold mb-4 text-black">Edit Small Defect for <span class="font-bold text-red-600 text-xl">{{ $sDefect['type'] }}</span></h2>
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
        <div class="flex-col w-11/12 sm:w-1/3 mt-3 ">
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