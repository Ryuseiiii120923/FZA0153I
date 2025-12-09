<div id="OuterPanel" class="outer-panel">
    <div class=" flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center" id="inspectors">
        <h1 class="text-center">Inspector(s)</h1>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            @if(!empty($insp1))
            <label for="insp1" class="block text-sm font-medium text-gray-700">{{ $name1 }}</label>
            @endif
            <input type="number" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif
                {{ in_array(1, $duplicateIndex) ? 'border-red-500' : 'border-black' }}" @if($locked) readonly @endif
                placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp1" id="insp1">
            @if($error1)
            <p class="text-red-500 text-sm">{{ $error1 }}</p>
            @endif
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            @if(!empty($insp2))
            <label for="insp2" class="block text-sm font-medium text-gray-700">{{ $name2 }}</label>
            @endif
            <input type="number" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif
                {{ in_array(2, $duplicateIndex) ? 'border-red-500' : 'border-black' }}" @if($locked) readonly @endif placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp2" id="insp2">
            @if($error2)
            <p class="text-red-500 text-sm">{{ $error2 }}</p>
            @endif
        </div>

        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            @if(!empty($insp3))
            <label for="insp3" class="block text-sm font-medium text-gray-700">{{ $name3 }}</label>
            @endif
            <input type="number" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif
                {{ in_array(3, $duplicateIndex) ? 'border-red-500' : 'border-black' }}" @if($locked) readonly @endif placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp3" id="insp3">
            @if($error3)
            <p class="text-red-500 text-sm">{{ $error3 }}</p>
            @endif
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            @if(!empty($insp4))
            <label for="insp4" class="block text-sm font-medium text-gray-700">{{ $name4 }}</label>

            @endif
            <input type="number" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif
                {{ in_array(4, $duplicateIndex) ? 'border-red-500' : 'border-black' }}" @if($locked) readonly @endif placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp4" id="insp4">
            @if($error4)
            <p class="text-red-500 text-sm">{{ $error4 }}</p>
            @endif
        </div>

        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            @if(!empty($insp5))
            <label for="insp5" class="block text-sm font-medium text-gray-700">{{ $name5 }}</label>
            @endif
            <input type="number" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($locked) opacity-20 cursor-not-allowed @endif
                {{ in_array(5, $duplicateIndex) ? 'border-red-500' : 'border-black' }}" @if($locked) readonly @endif placeholder=" " value="" wire:blur="CheckInsp" wire:model.lazy="insp5" id="insp5">
            @if($error5)
            <p class="text-red-500 text-sm">{{ $error5 }}</p>
            @endif
        </div>
    </div>
</div>