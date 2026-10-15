<?php

use App\Models\CheckHF;
use App\Services\General\InitialDataService;
use App\Services\Helpers\DraftService;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public int $excssQty = 0;
    public int $lackQty = 0;
    public int $reworkQty = 0;
    public int $sampleQty = 0;
    public float $ngRatioQty = 0.0;
    public int $goodQty = 0;
    public int $expectedQty = 0;
    public int $totalNg = 0;

    public bool $readOnly = false;
    public string $action = '';
    public int $ppf = 0;

    #[On('action-changed')]
    public function actionChanged(string $action)
    {
        $this->action = $action;
         $this->readOnly = false;
    }

    public function mount(int $ppf, bool $readOnly = false){
        $this->ppf = $ppf;
        $this->readOnly = $readOnly;

        if($ppf > 0){
            $this->checkedPPF($ppf);
        }
    }

    public function checkedPPF(int $ppf)
    {
        $this->ppf = $ppf;
        $this->expectedQty = CheckHF::where('流動NO', $ppf)->value('合格数') ?? 0;

        $draft = app(DraftService::class)->load((string) $ppf);

        if (!empty($draft['ppfLookup']['expct'])) {
            $this->expectedQty = $draft['ppfLookup']['expct'];
        }

        if (!empty($draft['defects']['totalNg'])) {
            $this->totalNg = $draft['defects']['totalNg'];
        }

        $this->fetchData($ppf);
        $this->computeQty();
         $this->syncToParent();
    }

    public function fetchData(int $ppf)
    {
        $result = app(InitialDataService::class)->loadComputedFieldQty($ppf);

        $this->excssQty  = $result['excssQty'] ?? 0;
        $this->lackQty   = $result['lackQty'] ?? 0;
        $this->sampleQty = $result['sampleQty'] ?? 0;
        $this->reworkQty = $result['reworkQty'] ?? 0;
        $this->goodQty   = $result['goodQty'] ?? 0;
    }

    public function updated($propertyName)
    {
        $this->computeQty();
        $this->syncToParent();
    }

    protected function syncToParent(): void
    {
        if (!$this->ppf) return;

        app(DraftService::class)->syncComponent((string) $this->ppf, 'computedFields', [
            'excssQty'   => $this->excssQty ?? 0,
            'lackQty'    => $this->lackQty ?? 0,
            'reworkQty'  => $this->reworkQty ?? 0,
            'sampleQty'  => $this->sampleQty ?? 0,
            'goodQty'    => $this->goodQty,
            'ngRatioQty' => $this->ngRatioQty,
        ]);
    }

    public function computeQty(): void
    {
        $this->goodQty = $this->expectedQty
            - $this->totalNg
            - ($this->lackQty ?? 0)
            - ($this->sampleQty ?? 0)
            + ($this->excssQty ?? 0);

        $this->ngRatioQty = $this->expectedQty > 0
            ? (float) number_format(($this->totalNg / $this->expectedQty) * 100, 2)
            : 0.0;
    }
};
?>

<div id="OuterPanel" class="outer-panel bg-white shadow-lg px-3 py-4">
    <div class="flex flex-col sm:flex-row  justify-center gap-4 mt-4 items-center">
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="excss" class="block text-sm font-medium text-gray-700">Excess Qty</label>
            <input type="number" id="excss" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly) cursor-not-allowed @endif"
                placeholder=" " value="" required
                onfocus="this.select()"
                wire:model.live.debounce.500ms="excssQty">
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="lack" class="block text-sm font-medium text-gray-700">Lacking Qty</label>
            <input type="number" id="lack" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly) cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " value="" required
                onfocus="this.select()"
                wire:model.live.debounce.500ms="lackQty" min="0">
        </div>

        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="rework" class="block text-sm font-medium text-gray-700">Rework Qty</label>
            <input type="number" id="rework" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly) cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " value="" required
                onfocus="this.select()"
                wire:model.live.debounce.500ms="reworkQty">
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="sample" class="block text-sm font-medium text-gray-700">Sample Qty</label>
            <input type="number" id="sample" inputmode="numeric"
                autocomplete="off"
                autocorrect="off"
                autocapitalize="off" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly) cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " required
                onfocus="this.select()"
                wire:model.live.debounce.500ms="sampleQty">
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="good" class="block text-sm font-medium text-gray-700">Good Qty</label>
            <input type="number" id="good" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly) cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " required wire:model.live="goodQty" readonly>
        </div>
        <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 ">
            <label for="ng" class="block text-sm font-medium text-gray-700">NG Ratio</label>
            <input type="number" id="ng" class="mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly) cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " required wire:model.live="ngRatioQty" readonly>
        </div>
    </div>
    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2">
        @error('excssqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
        @error('lackqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
        @error('reworkqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
        @error('sampleqty') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>
</div>