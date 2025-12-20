<div id="OuterPanel" class="outer-panel">
    <div class="flex flex-col sm:flex-row justify-center gap-4 mt-4 items-center" id="inspectors">
        <h1 class="text-center">Inspector(s)</h1>

        @php
            $inspectors = [
                ['model' => 'insp1', 'name' => $name1 ?? ''],
                ['model' => 'insp2', 'name' => $name2 ?? ''],
                ['model' => 'insp3', 'name' => $name3 ?? ''],
                ['model' => 'insp4', 'name' => $name4 ?? ''],
                ['model' => 'insp5', 'name' => $name5 ?? ''],
            ];
        @endphp

        @foreach($inspectors as $index => $insp)
            @php
                $prevModel = $index === 0 ? null : $inspectors[$index - 1]['model'];
                $isDisabled = $prevModel ? empty($$prevModel) : false;
                $errorVar = 'error' . ($index + 1);
                $duplicateClass = in_array($index + 1, $duplicateIndex ?? []) ? 'border-red-500' : 'border-black';
            @endphp

            <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2">
                @if(!empty($insp['name']))
                    <label for="{{ $insp['model'] }}" class="block text-sm font-medium text-gray-700">{{ $insp['name'] }}</label>
                @endif
                <input
                    type="number"
                    class="mt-1 block w-full border rounded-md px-2 py-1
                        @if($locked) opacity-20 cursor-not-allowed @endif
                        @if($isDisabled) bg-gray-500 @endif
                        {{ $duplicateClass }}"
                    id="{{ $insp['model'] }}"
                    wire:model.lazy="{{ $insp['model'] }}"
                    wire:blur="CheckInsp"
                    placeholder=" "
                    @if($isDisabled) disabled @endif
                    @if($locked) readonly @endif
                >
                @if($$errorVar)
                    <p class="text-red-500 text-sm">{{ $$errorVar }}</p>
                @endif
            </div>
        @endforeach
    </div>
</div>
