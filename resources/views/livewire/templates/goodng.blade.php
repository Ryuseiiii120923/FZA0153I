<div id="OuterPanel" class="outer-panel bg-white shadow-lg px-3 py-4">
    <div class="flex flex-col sm:flex-row  justify-center gap-4 mt-4 items-center">
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="excss" class="block text-sm font-medium text-gray-700">Excess Qty</label>
            <input type="number" id="excss" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked)  cursor-not-allowed @endif"
                placeholder=" " value="" wire:blur="updateNumbers" required wire:model="excssqty">
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="lack" class="block text-sm font-medium text-gray-700">Lacking Qty</label>
            <input type="number" id="lack" wire:blur="updateNumbers" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1  @if ($locklack) bg-gray-500 @endif  @if($locked)  cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="" required wire:model="lackqty" min="0">
        </div>


        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="rework" class="block text-sm font-medium text-gray-700">Rework Qty</label>
            <input type="number" id="rework" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked)  cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="" required wire:blur="updateNumbers" wire:model="reworkqty">
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="sample" class="block text-sm font-medium text-gray-700">Sample Qty</label>
            <input type="number" id="sample" wire:blur="updateNumbers" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked)  cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="" required wire:model="sampleqty">
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="good" class="block text-sm font-medium text-gray-700">Good Qty</label>
            <input type="number" id="good" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked)  cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="" required wire:model="goodqty" readonly>
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="ng" class="block text-sm font-medium text-gray-700">NG Ratio</label>
            <input type="number" id="ng" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked)  cursor-not-allowed @endif" @if($locked) readonly @endif
                placeholder=" " value="" required wire:model="ngratioqty" readonly>
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