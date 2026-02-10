    <div class="bg-white pb-5 px-2">
        <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4 ">
            <div class="w-11/12 sm:w-1/3 mx-5 sm:mx-2 flex flex-col items-start">
                <label for="PPF" class="block text-md font-medium text-black">PPF</label>
                <input type="number" id="PPF" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " wire:model.lazy="ppf" wire:keydown.enter="checkPPF">

            </div>
            <div class="w-11/12 sm:w-1/3 mt-6">
                @if($errorexisting)
                <p class="text-red-500 text-sm">{{ $errorexisting }}</p>
                @endif
                @error('ppf')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="w-full flex flex-col items-center sm:items-end">
                <button data-modal-target="static-modal-scanner" data-modal-toggle="static-modal-scanner"
                    class="text-white w-11/12 sm:w-1/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                    type="button" id="scan-ppf">
                    Scan PPF
                </button>
            </div>
        </div>


        @if ($systemname == "ProcessRecord")
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3 ">
            <label for="displayTotal" class="block text-sm font-medium text-black">Saved Total Inspection</label>
            <input
                type="number"
                id="displayTotal"
                wire:model="totalInspection"
                class="my-2 block w-full border border-black rounded-md px-2 py-1"
                readonly>
        </div>
        @endif



        <div
            x-data="{ open: @entangle('showInspectionModal') }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center">
            <!-- Black overlay -->
            <div class="absolute inset-0 bg-black bg-opacity-50"></div>

            <!-- Modal content -->
            <div class="relative bg-white rounded-lg shadow-xl w-96 p-6" @click.stop>
                <h2 class="text-lg font-semibold mb-4">Enter Total Inspection</h2>

                <input
                    type="number"
                    wire:model.defer="totalInspection"
                    class="w-full border rounded px-3 py-2 mb-4"
                    placeholder="Total inspection">

                @error('totalInspection')
                <p class="text-red-500 text-sm mb-2">{{ $message }}</p>
                @enderror

                <div class="flex justify-end gap-2">

                    <button
                        type="button"
                        wire:click="saveInspection"
                        class="px-4 py-2 bg-blue-600 text-white rounded">
                        Save
                    </button>
                </div>
            </div>
        </div>


        <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center">
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="PartNo" class="block text-sm font-medium text-black">Part No</label>
                <input type="text" id="PartNo" class="mt-1 block w-full border border-[#0F3C89] rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="partno" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="LotNo" class="block text-sm font-medium text-black">Lot No.</label>
                <input type="text" id="LotNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="lotno" readonly>
            </div>

            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="MatNo" class="block text-sm font-medium text-black">Material No.</label>
                <input type="text" id="MatNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="matno" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif ">
                <label for="MoldNo" class="block text-sm font-medium text-black">Molding Die No.</label>
                <input type="text" id="MoldNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="moldno" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2  @if($systemname == 'ProcessRecord') hidden @endif ">
                <label for="PressNo" class="block text-sm font-medium text-black">Press No.</label>
                <input type="text" id="PressNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="pressno" readonly>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row  justify-between gap-4 mt-4 items-center  @if($systemname == 'ProcessRecord') hidden @endif">
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="shift" class="block text-sm font-medium text-black">Shift</label>
                <input type="text" id="shift" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="shift" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2  @if($systemname == 'ProcessRecord') hidden @endif ">
                <label for="opt" class="block text-sm font-medium text-black">Operator</label>
                <input type="text" id="opt" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="opt" readonly>
            </div>
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2  @if($systemname == 'ProcessRecord') hidden @endif ">
                <label for="expct" class="block text-sm font-medium text-black">Expected Qty</label>
                <input type="text" id="expct" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                    placeholder=" " value="" required wire:model="expct" required readonly>
            </div>
        </div>
        <div class="flex flex-col mt-3 w-11/12 sm:w-1/3 mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif"
            @if($isPPF) wire:poll.2s="totalInspectedProgress" @endif>

            <!-- Label -->
            <label for="ProgressInsp" class="block text-sm font-medium text-black">Inspection Progress</label>

            <!-- Input + Button on same line -->
            <div class="flex items-center gap-2 mt-1">
                <input type="text"
                    id="ProgressInsp"
                    class="flex-1 border border-black rounded-md px-2 py-1 me-4"
                    placeholder=" "
                    required
                    wire:model="progressInsp"
                    readonly>

                <button type="button"
                    wire:click="confirmAccept"
                    class="@if($isAccept || $action != 'Add') hidden @endif px-4 py-1 ms-3 bg-blue-600 text-white rounded-md
               hover:bg-blue-700 hover:shadow-md transition duration-200">
                    Accept
                </button>
            </div>
        </div>
    </div>