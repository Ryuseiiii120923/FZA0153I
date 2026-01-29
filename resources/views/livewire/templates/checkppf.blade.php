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
    </div>