    <div class="bg-white shadow-lg px-3 py-4">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">

            <!-- PPF Input (smaller width) -->
            <div class="flex flex-col mx-5 sm:mx-2">
                <label for="PPF" class="block text-md font-medium text-black mb-1">PPF</label>
                <input
                    type="number"
                    id="PPF"
                    class="w-40 border border-black   w-full rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Enter PPF"
                    wire:model.lazy="ppf"
                    wire:keydown.enter="EnterPPF">

                <!-- Error Messages -->
                @if($errorexisting || $errors->has('ppf'))
                <div class="mt-1">
                    @if($errorexisting)
                    <p class="text-red-500 text-sm">{{ $errorexisting }}</p>
                    @endif
                    @error('ppf')
                    <p class="text-red-500 text-sm">{{ $message }}</p>
                    @enderror
                </div>
                @endif
            </div>

            <!-- Button -->
            <div class="flex-shrink-0 mx-5 sm:mx-2 ">
                <button
                    data-modal-target="static-modal-scanner"
                    data-modal-toggle="static-modal-scanner"
                    type="button"
                    id="scan-ppf" style="height: 45px;"
                    class="bg-[#0F3C89] hover:bg-blue-800 
                   text-white font-medium rounded-lg text-sm 
                   px-6 py-2 w-full transition duration-200 
                   focus:ring-4 focus:ring-blue-300">
                    Scan PPF
                </button>
            </div>

        </div>

        <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center">
            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
                <label for="PartNo" class="block text-sm font-medium text-black">Part No</label>
                <input type="text" id="PartNo" class="mt-1 block w-full border border-black  rounded-md px-2 py-1"
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
        <div class="flex flex-col mt-3 w-11/12 sm:w-1/3 mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif" >
                {{--  @if($isPPF) wire:poll.60s="totalInspectedProgress" @endif --}}

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
               {{-- <button type="button"
                    wire:click="confirmAccept"
                    class="@if($isAccept || $actiondash != 'add') hidden @endif px-4 py-1 ms-3 bg-blue-600 text-white rounded-md
               hover:bg-blue-700 hover:shadow-md transition duration-200">
                    Accept
                </button> --}} 
            </div>
        </div>
    </div>