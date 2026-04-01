<div x-data="{ openAddRework: false }" class="bg-white px-3 py-4 rounded-lg w-full mx-auto @if($locked) opacity-50 cursor-not-allowed @endif">
    <div class="bg-white shadow-md max-w-3xl mx-auto px-3 py-4 rounded-lg h-[500px] flex flex-col">
        <div class="bg-gray-700 w-full">
            <p class="text-4xl font-extrabold  text-center text-white p-4 ">Rework</p>
        </div>

        <div class="overflow-x-auto mt-3">
            <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
                <thead class="bg-gray-900 text-white text-left">
                    <tr>
                        <th class="px-4 py-2">Inspector ID</th>
                        <th class="px-4 py-2">Inspector Name</th>
                        <th class="px-4 py-2">HFNo</th>
                        <th class="px-4 py-2">RWK Defect</th>
                        <th class="px-4 py-2">Qty</th>
                        <th class="px-4 py-2">Total Insp</th>
                        <th class="px-4 py-2">Date Encode</th>
                    </tr>
                </thead>
                <tbody class="bg-gray-700">
                    @forelse ( $reworkss as $reworks )
                    <tr wire:key="rework-{{ $reworks['type'] }}">
                        <td class="px-4 py-2">{{ $reworks['operatorid'] }}</td>
                        <td class="px-4 py-2">{{ $reworks['operatorname'] }}</td>
                        <td class="px-4 py-2">{{ $reworks['hfno'] }}</td>
                        <td class="px-4 py-2">{{ $reworks['type'] }}</td>
                        <td class="px-4 py-2">{{ $reworks['quan'] }}</td>
                        <td class="px-4 py-2">{{ $reworks['totalinsp'] }}</td>
                        <td class="px-4 py-2">{{ $reworks['dateEncode'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center">No rework added yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
                <label for="TotalNgRework" class="block text-sm font-medium text-black">Total NG Rework</label>
                <input type="text" id="TotalNgRework" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " required wire:model="totalngrework" readonly>
            </div>
        </div>
        @error('hfno')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        @error('totalInsp')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        @error('newRework')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        @error('newQuan')
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

</div>