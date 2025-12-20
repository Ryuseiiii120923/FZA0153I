<div>
    <div class="flex flex-col justify-center">

        <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4">
            <div class="w-11/12 sm:w-1/3 mx-5 sm:mx-2 flex flex-col items-start">
                <label for="PPF" class="block text-sm font-medium text-gray-700">PPF</label>
                <input type="text" id="PPF" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="ppf" wire:keydown.enter="loadppf">

            </div>
            <div class="w-11/12 sm:w-1/3 mt-6">
                @error('ppf')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full flex flex-col items-center sm:items-end">
                <button data-modal-target="static-modal-scanner" data-modal-toggle="static-modal-scanner"
                    class="text-white w-11/12 sm:w-1/3 bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                    type="button" id="scan-ppf" hidden>
                    Scan PPF
                </button>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center">
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="PartNo" class="block text-sm font-medium text-gray-700">Part No</label>
                <input type="text" id="PartNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="partno" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="LotNo" class="block text-sm font-medium text-gray-700">Lot No.</label>
                <input type="text" id="LotNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="lotno" readonly>
            </div>

            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="MatNo" class="block text-sm font-medium text-gray-700">Material No.</label>
                <input type="text" id="MatNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="matno" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="MoldNo" class="block text-sm font-medium text-gray-700">Molding Die No.</label>
                <input type="text" id="MoldNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="moldno" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="PressNo" class="block text-sm font-medium text-gray-700">Press No.</label>
                <input type="text" id="PressNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="pressno" readonly>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row  justify-between gap-4 mt-4 items-center">
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="shift" class="block text-sm font-medium text-gray-700">Shift</label>
                <input type="text" id="shift" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="shift" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="opt" class="block text-sm font-medium text-gray-700">Operator</label>
                <input type="text" id="opt" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="opt" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="expct" class="block text-sm font-medium text-gray-700">Expected Qty</label>
                <input type="text" id="expct" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="expct" required readonly>
            </div>
        </div>



        <div class="flex flex-col sm:flex-row gap-6 mt-4 items-start justify-center w-full px-4">
            <div class="w-11/12 sm:w-1/2 flex justify-center">
                <div class="bg-white  rounded-lg w-full max-w-md mx-auto p-4" id="OuterPanel">
                    <p class="text-4xl font-extrabold bg-gray-700 w-full text-center text-white p-4">Defect</p>
                    <div class="w-full flex flex-col items-center mt-5">
                        <button data-modal-target="static-modal-defect" data-modal-toggle="static-modal-defect"
                            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                            type="button" id="add-defect">
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
                                <tr>
                                    <td class="px-4 py-2">{{ $defect['type'] }}</td>
                                    <td class="px-4 py-2">{{ $defect['qty'] }}</td>
                                    <td class="px-4 py-2 flex justify-center gap-2">

                                        <div x-data="{ openSmall: false }">
                                            <button
                                                @click="openSmall = true"
                                                class="text-white bg-green-700 px-4 py-2 rounded"
                                                wire:click="loadSmallDefects('{{ $defect['type'] }}')">
                                                Add Small Defect
                                            </button>


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
                                                            <input
                                                                type="text"
                                                                id="defectTypesmall"
                                                                class="my-2 block w-full border border-black rounded-md px-2 py-1 text-black"
                                                                list="data-list-small-defects"
                                                                placeholder=" "
                                                                wire:model.defer="newSmallDefect">

                                                            <datalist id="data-list-small-defects">
                                                                @foreach ($SmallDefectsForModal ?? collect() as $s)
                                                                <option value="{{ is_object($s) ? $s->SmallDefect : $s }}"></option>
                                                                @endforeach
                                                            </datalist>

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
                                        </div>

                                        <button class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded"
                                            wire:click="deleteDefect('{{ $defect['type'] }}')"
                                            onclick="confirm('Are you sure you want to delete this record?')">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                                @if(isset($smallDefects[$defect['type']]))
                                @foreach($smallDefects[$defect['type']] as $sDefect)
                                <tr class="bg-gray-500">
                                    <td class="px-8 py-1">{{ $sDefect['type'] }}</td>
                                    <td class="px-4 py-1">{{ $sDefect['qty'] }}</td>
                                    <td></td>
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

                    <div id="static-modal-defect" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
                        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                        <div class="relative p-4 w-full max-w-2xl max-h-full">
                            <!-- Modal content -->
                            <div class="relative bg-white rounded-lg shadow-sm">
                                <!-- Modal header -->
                                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        Add Defect
                                    </h3>
                                    <button type="button"
                                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                                        data-modal-hide="static-modal-defect" id="defect-id-close">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Modal body -->
                                <div class="flex flex-col justify-center gap-4 my-4 items-center">
                                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                                        <label for="defectType" class="block text-sm font-medium text-black">Defect Type</label>
                                        <input type="text" id="defectType" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                            placeholder=" " list="data-list-defects" required wire:model="newDefect">
                                        <datalist id="data-list-defects">
                                            @foreach ($Largedefects as $Ldefects)
                                            <option value="{{ $Ldefects->LargeDefect }}"></option>
                                            @endforeach
                                        </datalist>
                                        @error('newDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                                        <label for="qty" class="block text-sm font-medium text-black">Quantity</label>
                                        <input type="text" id="qty" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                            placeholder=" " required wire:model="newQuan">
                                        @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                    </div>

                                    <button wire:click="addDefect" id="addDefect" type="button"
                                        class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-[#0F3C89] hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2">
                                        Add Defect
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w-11/12 sm:w-1/2 flex justify-center">
                <div class="bg-white rounded-lg w-full max-w-md mx-auto p-4">
                    <p class="text-4xl font-extrabold bg-gray-700 w-full text-center text-white p-4 ">Rework</p>
                    <div class="w-full flex flex-col items-center mt-5">
                        <button data-modal-target="static-modal-rework" data-modal-toggle="static-modal-rework"
                            class="text-white w-11/12 sm:w-2/3 bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                            type="button" id="add-rework">
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
                                </tr>
                            </thead>
                            <tbody class="bg-gray-700">
                                @forelse ( $reworks as $rework )
                                <tr>
                                    <td class="px-4 py-2">{{ $rework['HFNo'] }}</td>
                                    <td class="px-4 py-2">{{ $rework['Defect'] }}</td>
                                    <td class="px-4 py-2">{{ $rework['Quantity'] }}</td>
                                    <td class="px-4 py-2">{{ $rework['TotalInspQty'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center">No rework added yet.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
                            <label for="HfNo" class="block text-sm font-medium text-black">Total NG Rework</label>
                            <input type="text" id="HfNo" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                placeholder=" " required wire:model="totalngrework">
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
                    <div wire:ignore.self id="static-modal-rework" data-modal-backdrop="static" tabindex="-1" aria-hidden="true"
                        class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                        <div class="relative p-4 w-full max-w-2xl max-h-full">
                            <!-- Modal content -->
                            <div class="relative bg-white rounded-lg shadow-sm">
                                <!-- Modal header -->
                                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-200">
                                    <h3 class="text-xl font-semibold text-gray-900">
                                        Add Rework
                                    </h3>
                                    <button type="button"
                                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                                        data-modal-hide="static-modal-rework" id="rework-id-close">
                                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                                            viewBox="0 0 14 14">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                        </svg>
                                    </button>
                                </div>
                                <!-- Modal body -->
                                <div class="flex flex-col justify-center gap-4 my-4 items-center">

                                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                                        <label for="HfNo" class="block text-sm font-medium text-black">HF No.</label>
                                        <div class="flex sm:flex-row flex col gap-3">
                                            <input type="text" id="HfNo" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                                placeholder=" " required wire:blur="CheckHf" wire:model.lazy="hfno">
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
                                        <label for="defectTypeUpdate" class="block text-sm font-medium text-black">Rework Defect</label>
                                        <input type="text" id="defectTypeUpdate" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                            placeholder=" " list="data-list-rework" required wire:model="newRework">
                                        <datalist id="data-list-rework">
                                            @foreach ($reworkOption as $reworksOption)
                                            <option value="{{ $reworksOption->DefectType }}"></option>
                                            @endforeach
                                        </datalist>
                                        @error('newRework') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                                        <label for="qty" class="block text-sm font-medium text-black">Quantity</label>
                                        <input type="text" id="qty" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                                            placeholder=" " required wire:model="newQuan">
                                        @error('newQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                                    </div>

                                    <button wire:click="addRework" id="addRework" type="button"
                                        class="w-full sm:w-1/3 px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2">
                                        Add Rework
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="flex flex-col sm:flex-row  justify-center gap-4 mt-4 items-center">
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    <label for="excss" class="block text-sm font-medium text-gray-700">Excess Qty</label>
                    <input type="text" id="excsss" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        placeholder=" " value="" required wire:model="excssqty">
                </div>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 " wire:ignore>
                    <label for="lack" class="block text-sm font-medium text-gray-700">Lacking Qty</label>
                    <input type="text" id="lacks" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        placeholder=" " value="" required wire:model="lackqty" min="0">
                </div>


                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    <label for="rework" class="block text-sm font-medium text-gray-700">Rework Qty</label>
                    <input type="text" id="reworks" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        placeholder=" " value="" required wire:model="reworkqty">
                </div>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    <label for="sample" class="block text-sm font-medium text-gray-700">Sample Qty</label>
                    <input type="text" id="samples" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        placeholder=" " value="" wire:blur="GoodNg" required wire:model.lazy="sampleqty">
                </div>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    <label for="good" class="block text-sm font-medium text-gray-700">Good Qty</label>
                    <input type="text" id="goods" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        placeholder=" " value="" required wire:model="goodqty">
                </div>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    <label for="ng" class="block text-sm font-medium text-gray-700">NG Ratio</label>
                    <input type="text" id="ngs" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                        placeholder=" " value="" required wire:model="ngratioqty">
                </div>
                <button hidden wire:click="GoodNg" id="GoodNg"></button>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2">
                @error('excssqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                @error('lackqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                @error('reworkqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
                @error('sampleqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror

            </div>
        </div>
        <div>
            <div class=" flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center" id="inspectors">
                <h1 class="text-center">Inspector(s)</h1>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    @if(!empty($insp1))
                    <label for="insp1" class="block text-sm font-medium text-gray-700">{{ $name1 }}</label>
                    @endif
                    <input type="text" class=" insp mt-1 block w-full border border-black rounded-md px-2 py-1 {{ in_array(1, $duplicateIndex) ? 'border-red-500' : 'border-black' }}"
                        placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp1" id="insp1">
                    @if($error1)
                    <p class="text-red-500 text-sm">{{ $error1 }}</p>
                    @endif
                </div>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    @if(!empty($insp2))
                    <label for="insp2" class="block text-sm font-medium text-gray-700">{{ $name2 }}</label>
                    @endif
                    <input type="text" class=" insp mt-1 block w-full border border-black rounded-md px-2 py-1 {{ in_array(2, $duplicateIndex) ? 'border-red-500' : 'border-black' }}"
                        placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp2" id="insp2">
                    @if($error2)
                    <p class="text-red-500 text-sm">{{ $error2 }}</p>
                    @endif
                </div>

                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    @if(!empty($insp3))
                    <label for="insp3" class="block text-sm font-medium text-gray-700">{{ $name3 }}</label>
                    @endif
                    <input type="text" class=" insp mt-1 block w-full border border-black rounded-md px-2 py-1 {{ in_array(3, $duplicateIndex) ? 'border-red-500' : 'border-black' }}"
                        placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp3" id="insp3">
                    @if($error3)
                    <p class="text-red-500 text-sm">{{ $error3 }}</p>
                    @endif
                </div>
                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    @if(!empty($insp4))
                    <label for="insp4" class="block text-sm font-medium text-gray-700">{{ $name4 }}</label>

                    @endif
                    <input type="text" class=" insp mt-1 block w-full border border-black rounded-md px-2 py-1 {{ in_array(4, $duplicateIndex) ? 'border-red-500' : 'border-black' }}"
                        placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp4" id="insp4">
                    @if($error4)
                    <p class="text-red-500 text-sm">{{ $error4 }}</p>
                    @endif
                </div>

                <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                    @if(!empty($insp5))
                    <label for="insp5" class="block text-sm font-medium text-gray-700">{{ $name5 }}</label>
                    @endif
                    <input type="text" class=" insp mt-1 block w-full border border-black rounded-md px-2 py-1 {{ in_array(5, $duplicateIndex) ? 'border-red-500' : 'border-black' }}"
                        placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp5" id="insp5">
                    @if($error5)
                    <p class="text-red-500 text-sm">{{ $error5 }}</p>
                    @endif
                </div>
            </div>
        </div>
        @if (session()->has('success'))
        <div
            x-data="{ open: true }"
            x-show="open"
            class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50"
            x-cloak>
            <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
                <!-- Close Button -->
                <button
                    @click="open = false"
                    class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                    ✕
                </button>

                <!-- Modal Content -->
                <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
                <p class="text-gray-700 mb-4">{{ session('success') }}</p>

                <button
                    @click="open = false;
            location.reload();
            "
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    OK
                </button>
            </div>
        </div>
        @endif
        <div class=" flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center">
            <div class="w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="automach" class="block text-sm font-medium text-gray-700">Auto Machine</label>
                <input type="text" id="automach" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " list="data-list-automach" value="" required wire:model="auto">
                <datalist id="data-list-automach">
                    <option value="Vario 1">Vario 1</option>
                    <option value="Vario 2">Vario 2</option>
                    <option value="Vario 4">Vario 4</option>
                    <option value="">Assy 1</option>
                    <option value="">Assy 2</option>
                </datalist>
            </div>
            <div class="w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="plant" class="block text-sm font-medium text-gray-700">Plant</label>
                <input type="text" id="plant" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " list="data-list-plant" value="" required wire:model="plant">
                <datalist id="data-list-plant">
                    <option value="Plant 1A">Plant 1A</option>
                    <option value="Plant 1B">Plant 1B</option>
                    <option value="Plant 2A">Plant 2A</option>
                    <option value="Plant 2B">Plant 2B</option>
                </datalist>
            </div>
        </div>

        <div class=" flex flex-col gap-4 mt-4 items-center mx-6 sm:mx-2">
            <div class="w-full">
                <label for="inspectDate" class="block text-sm font-medium text-gray-700">Inspection Date</label>
                <input type="date" id="inspectDate" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " wire:model="inspectiondate" required>
            </div>

            <div class="w-full">
                <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
                <input type="text" id="details" class="text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " required wire:model="details">
            </div>
        </div>

        <div class=" flex flex-col gap-4 mt-4 items-center mx-6 sm:mx-2">
            <div class="w-full">
                <label for="upd" class="block text-sm font-medium text-gray-700">Update Date</label>
                <input type="date" id="upd" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " wire:model="dateencode" required readonly>
            </div>

            <div class="w-full">
                <label for="registrant" class="block text-sm font-medium text-gray-700">Registrant</label>
                <input type="text" id="registrant" class="text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " required readonly wire:model="username">
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-center mx-4 mb-4 p-4 mt-5">
            <button type="button" class="w-full px-6 py-3.5 text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2" wire:click="AddtoDb">{{ $newbuttonname }}</button>
        </div>
    </div>
</div>