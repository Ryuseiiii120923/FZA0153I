<div x-data="{ openAddRework: false }" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($locked) opacity-50 cursor-not-allowed @endif">
     <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold  text-center text-white p-4 ">Rework</p>
    </div>
    <div class="w-full flex flex-col items-center mt-5">
        <button @click="openAddRework = true; $nextTick(() => $refs.firstInput?.focus())"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            type="button" id="add-rework" @if($locked) disabled @endif>
            Add Rework
        </button>
    </div>
    <div class="overflow-x-auto mt-3 mx-3">
        <table class=" table-auto w-full text-sm text-white bg-gray-800 rounded-lg overflow-hidden">
            <thead class="bg-gray-900 text-white text-left">
                <tr>
                    <th class="px-4 py-2">HFNo</th>
                    <th class="px-4 py-2">RWK Defect</th>
                    <th class="px-4 py-2">Qty</th>
                    <th class="px-4 py-2">Total Insp</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="bg-gray-700">
                @forelse ( $reworkss as $reworks )
                <tr wire:key="rework-{{ $reworks['type'] }}">
                    <td class="px-4 py-2">{{ $reworks['hfno'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['type'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['quan'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['totalinsp'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">
                        <div x-data="{ openSmall: false }" class="flex justify-center">

                            <div class="flex gap-3">
                                <button
                                    @if($locked) disabled @endif
                                    @click="openSmall = true"
                                    wire:click="startEdit('{{ $reworks['type'] }}', '{{ $reworks['hfno'] }}')"
                                    class="text-white bg-green-700 px-4 py-2 rounded">
                                    Edit
                                </button>

                                <!-- DELETE BUTTON -->
                                <button
                                    @if($locked) disabled @endif
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                    @click.prevent="
            if (confirm('Are you sure you want to delete this record?')) {
                 $wire.deleteRework(
            @js($reworks['hfno']),
            @js($reworks['type'])
        );
            }
        ">
                                    Delete
                                </button>
                            </div>


                            <!-- BACKDROP -->
                            <div
                                x-show="openSmall"
                                x-transition.opacity
                                class="fixed inset-0 bg-black bg-opacity-40 z-40"
                                style="display: none"></div>

                            <!-- MODAL WRAPPER -->
                            <div
                                x-show="openSmall"
                                x-transition
                                class="fixed top-0 right-0 left-0 z-50 justify-center items-center flex w-full md:inset-0 h-[calc(100%-1rem)] max-h-full"
                                style="display: none">
                                <div class="relative p-4 w-full max-w-2xl max-h-full">

                                    <!-- MODAL CONTENT -->
                                    <div class="relative bg-white rounded-lg shadow-sm">

                                        <!-- HEADER (copied style) -->
                                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                            <h3 class="text-xl font-semibold text-gray-900">
                                                Edit Rework for
                                                <span class="font-bold text-red-600 text-xl">{{ $reworks['type']}}  (HFNO {{ $reworks['hfno'] }})</span>
                                            </h3>

                                            <button
                                                @if($locked) disabled @endif
                                                type="button"
                                                @click="openSmall = false"
                                                wire:click="$set('newQuan', ''); $set('totalInsp', '')"
                                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center">
                                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                                    viewBox="0 0 14 14">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                                </svg>
                                            </button>
                                        </div>

                                        <!-- BODY -->
                                        <div class="flex flex-col justify-center gap-4 my-4 items-center">

                                            <!-- QUANTITY -->
                                            <div class="flex-col w-11/12 sm:w-1/3">
                                                <label class="block text-sm font-medium text-black">Quantity</label>

                                                <input
                                                    type="text"
                                                    class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                    wire:model.defer="newQuan">

                                                @error('newQuann')
                                                <p class="text-red-500 text-sm">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div class="flex-col w-11/12 sm:w-1/3">
                                                <label class="block text-sm font-medium text-black">Total Insp</label>

                                                <input
                                                    type="text"
                                                    class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                    wire:model.defer="totalInsp">

                                                @error('totalInsp')
                                                <p class="text-red-500 text-sm">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="w-full flex flex-col items-center mb-5">
                                                <button
                                                    @if($locked) disabled @endif
                                                    wire:click="updateRework"
                                                    @click="$nextTick(() => openSmall = false)"
                                                    type="button"
                                                    class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm text-center">
                                                    Edit
                                                </button>
                                            </div>


                                        </div><!-- END BODY -->

                                    </div><!-- END CONTENT -->

                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
               <tr>
                    <td colspan="5" class="px-6 py-4 text-center">
                        No defects added yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
            <label for="HfNo" class="block text-sm font-medium text-black">Total NG Rework</label>
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
    <div x-show="openAddRework" x-cloak x-trap.noscroll="openAddRework" x-transition class="fixed inset-0 flex items-center justify-center z-50 p-4">
        <div @click="openAddRework = false" x-show="openAddRework" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40"></div>
        <div class="relative bg-white rounded-lg shadow-sm w-full max-w-md z-50 p-6">
            <div class="flex items-center justify-between p-4 border-b rounded-t border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Add Rework</h3>
                <button type="button" @click="openAddRework=false" class="text-gray-400 hover:text-gray-900 rounded-lg w-8 h-8 flex justify-center items-center">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                </button>
            </div>
            <div class="flex flex-col justify-center gap-4 mt-4">
                <div class="flex-col w-11/12 sm:w-full mx-auto">


                    <label for="HfNo" class="block text-sm font-medium text-black">HF No.</label>

                    <div class="flex items-center gap-3">
                        <input type="number" id="HF" x-ref="firstInput" class="my-2 block w-full border border-black rounded-md px-2 py-1" wire:blur="CheckHf" wire:model.lazy="hfno">
                        @if(!empty($hfno))
                        <p class="text-sm font-medium text-black">{{ $hfname }}</p>
                        @endif
                    </div>


                    @error('hfno') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                </div>

                <div class="flex-col w-11/12 sm:w-full mx-auto">
                    <label for="totalInspct" class="block text-sm font-medium text-black">Total Inspct Qty.</label>
                    <input type="number" id="totalInspct" class="my-2 block w-full border border-black rounded-md px-2 py-1" wire:model="totalInsp">
                    @error('totalInsp') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="flex-col w-11/12 sm:w-full mx-auto">
                    <label for="defectType" class="block text-sm font-medium text-black">Rework Defect</label>
                    <select id="defectType" wire:model="newRework" class="my-2 block w-full border border-black rounded-md px-2 py-1">
                        <option>-- Select Rework --</option>
                        @foreach ($rework as $r)
                        <option value="{{ $r->DefectType }}">{{ $r->DefectType }}</option>
                        @endforeach
                    </select>
                    @error('newRework') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <div class="flex-col w-11/12 sm:w-full mx-auto">
                    <label for="qty" class="block text-sm font-medium text-black">Quantity</label>
                    <input type="number" id="qty" class="my-2 block w-full border border-black rounded-md px-2 py-1" wire:model="newQuan">
                    @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                </div>

                <button wire:click="addRework" @click="openAddRework = false" class="w-full px-6 py-3.5 text-white bg-[#0F3C89] hover:bg-blue-800 rounded-full text-sm mt-2">
                    Add Rework
                </button>
            </div>
        </div>
    </div>

</div>