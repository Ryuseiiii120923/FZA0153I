<div class="bg-white rounded-lg w-full max-w-md mx-auto @if($locked) opacity-50 cursor-not-allowed @endif" 
     id="OuterPanel" x-data="{ addModalOpen: false, editModalOpen: {}, openEdit: null }" x-cloak>

    <p class="text-4xl font-extrabold bg-gray-700 w-full text-center text-white p-4 ">Rework</p>

    <!-- ADD REWORK BUTTON -->
    <div class="w-full flex flex-col items-center mt-5">
        <button 
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            type="button" 
            @click="addModalOpen = true"
            @if($locked) disabled @endif>
            Add Rework
        </button>
    </div>

    <!-- TABLE -->
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
                @forelse ($reworkss as $reworks)
                <tr wire:key="rework-{{ $reworks['type'] }}">
                    <td class="px-4 py-2">{{ $reworks['hfno'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['type'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['quan'] }}</td>
                    <td class="px-4 py-2">{{ $reworks['totalinsp'] }}</td>
                    <td class="px-4 py-2 flex justify-center gap-2">

                        <!-- EDIT BUTTON -->
                        <div class="flex gap-3">
                            <button
                                class="text-white bg-green-700 px-4 py-2 rounded"
                                @click="openEdit = '{{ $reworks['type'] }}'"
                                wire:click="startEdit('{{ $reworks['type'] }}')"
                                @if($locked) disabled @endif>
                                Edit
                            </button>

                            <button
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                @click.prevent="if(confirm('Are you sure you want to delete this record?')) { $wire.deleteRework(@js($reworks['type'])); }"
                                @if($locked) disabled @endif>
                                Delete
                            </button>
                        </div>

                        <!-- EDIT MODAL -->
                        <div x-show="openEdit === '{{ $reworks['type'] }}'" x-transition.opacity 
                             class="fixed inset-0 bg-black bg-opacity-40 z-40"></div>

                        <div x-show="openEdit === '{{ $reworks['type'] }}'" x-transition
                             class="fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full h-[calc(100%-1rem)] max-h-full">
                            <div class="relative p-4 w-full max-w-2xl max-h-full">

                                <!-- MODAL CONTENT -->
                                <div class="relative bg-white rounded-lg shadow-sm">
                                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                        <h3 class="text-xl font-semibold text-gray-900">
                                            Edit Rework for
                                            <span class="font-bold text-red-600 text-xl">{{ $reworks['type'] }}</span>
                                        </h3>

                                        <button type="button"
                                                class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center"
                                                @click="openEdit = null; $wire.set('newQuan',''); $wire.set('totalInsp','')"
                                                @if($locked) disabled @endif>
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div class="flex flex-col justify-center gap-4 my-4 items-center">
                                        <div class="flex-col w-11/12 sm:w-1/3">
                                            <label class="block text-sm font-medium text-black">Quantity</label>
                                            <input type="text" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                   wire:model.defer="newQuan">
                                            @error('newQuann') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="flex-col w-11/12 sm:w-1/3">
                                            <label class="block text-sm font-medium text-black">Total Insp</label>
                                            <input type="text" class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                   wire:model.defer="totalInsp">
                                            @error('totalInsp') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                        </div>
                                        <div class="w-full flex flex-col items-center mb-5">
                                            <button wire:click="updateRework"
                                                    @click="$nextTick(() => openEdit = null)"
                                                    class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 rounded-full">
                                                Edit
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <!-- END EDIT MODAL -->

                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-4 py-2 text-center">No rework added yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TOTAL NG REWORK -->
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
            <label for="HfNo" class="block text-sm font-medium text-black">Total NG Rework</label>
            <input type="text" id="TotalNgRework" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                   wire:model="totalngrework" readonly>
        </div>
    </div>

    <!-- ADD REWORK MODAL -->
    <div x-show="addModalOpen" x-transition.opacity class="fixed inset-0 bg-black bg-opacity-40 z-40"></div>
    <div x-show="addModalOpen" x-transition
         class="fixed top-0 right-0 left-0 z-50 flex justify-center items-center w-full h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">

            <div class="relative bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Add Rework</h3>
                    <button type="button"
                            @click="addModalOpen = false"
                            class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center"
                            @if($locked) disabled @endif>
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col justify-center gap-4 my-4 items-center">

                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                        <label for="HfNo" class="block text-sm font-medium text-black">HF No.</label>
                        <div class="flex sm:flex-row flex col gap-3">
                            <input type="text" id="HfNos" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                placeholder=" " required wire:blur="CheckHf" wire:model.lazy="hfno" readonly>
                            @if(!empty($hfno))
                            <p class="text-sm font-medium text-black mt-3">{{ $hfname }}</p>
                            @endif
                        </div>

                        @error('hfno') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

                    </div>
                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                        <label for="totalInspct" class="block text-sm font-medium text-black">Total Inspct Qty.</label>
                        <input type="text" id="totalInspct" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                            placeholder=" " required wire:model="totalInsp">
                        @error('totalInsp') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                        <label for="defectType" class="block text-sm font-medium text-black">Rework Defect</label>

                        <select id="defectType" wire:model="newRework" class="my-2 block w-full border border-black rounded-md px-2 py-1">
                            <option > -- Select Rework --</option>
                              @foreach ($rework as $reworks)
                            <option value="{{ $reworks->DefectType }}">{{ $reworks->DefectType }}</option>
                            @endforeach
                        </select>
                        @error('newRework') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                        <label for="qty" class="block text-sm font-medium text-black">Quantity</label>
                        <input type="text" id="qty" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                            placeholder=" " required wire:model="newQuan">
                        @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                    </div>

                    <button wire:click="addRework" id="addRework" type="button" @click="addModalOpen = false"
                        class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-[#0F3C89] hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2">
                        Add Rework
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>