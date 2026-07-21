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
        <div class="flex flex-col mt-3 w-11/12 sm:w-1/3 mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif"
            @if($isPPF) wire:poll.60s="totalInspectedProgress" @endif>

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
                    class="@if($isAccept || $actiondash != 'add') hidden @endif px-4 py-1 ms-3 bg-blue-600 text-white rounded-md
               hover:bg-blue-700 hover:shadow-md transition duration-200">
                    Accept
                </button>
            </div>
        </div>

        <!-- FIFO Override Authorization Dialog -->
        <div
            x-data="{ show: false }"
            x-init="$watch('$wire.showFifoAuthModal', value => show = value)"
            x-show="show"
            x-cloak
            x-trap.inert.noscroll="show"
            @keydown.escape.window="$wire.cancelFifoAuth()"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="display: none;">

            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50" @click="$wire.cancelFifoAuth()"></div>

            <!-- Dialog -->
            <div
                x-show="show"
                x-transition
                @click.stop
                class="relative bg-white rounded-lg shadow-xl w-6/12 sm:w-96 p-6">

                <h2 class="text-lg font-semibold text-black mb-2">Supervisor Authorization Required</h2>

                @if($fifoAuthMessage)
                <p class="text-sm text-gray-700 mb-4">{{ $fifoAuthMessage }}</p>
                @endif

                <form wire:submit.prevent="authorizeFifoOverride" class="space-y-3">
                    <div>
                        <label for="authUsername" class="block text-sm font-medium text-black">Username</label>
                        <input
                            type="text"
                            id="authUsername"
                            autocomplete="off"
                            wire:model="authUsername"
                            class="mt-1 block w-full border border-black rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('authUsername')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="authPassword" class="block text-sm font-medium text-black">Password</label>
                        <input
                            type="password"
                            id="authPassword"
                            autocomplete="off"
                            wire:model="authPassword"
                            class="mt-1 block w-full border border-black rounded-md px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('authPassword')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($authError)
                    <p class="text-red-500 text-sm">{{ $authError }}</p>
                    @endif

                    <div class="flex justify-end gap-2 pt-2 mt-4">
                        <button
                            type="button"
                            wire:click="cancelFifoAuth"
                            class="px-4 py-1 rounded-md border border-gray-400 text-gray-700 hover:bg-gray-100 transition duration-200">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="authorizeFifoOverride"
                            class="px-4 py-1 bg-[#0F3C89] text-white rounded-md hover:bg-blue-800 transition duration-200">
                            <span wire:loading.remove wire:target="authorizeFifoOverride">Authorize</span>
                            <span wire:loading wire:target="authorizeFifoOverride">Checking...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>