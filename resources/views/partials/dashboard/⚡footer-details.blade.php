<?php

use App\Repositories\General\InitialDataRepository;
use App\Services\General\InitialDataService;
use App\Services\Helpers\DraftService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth as UserAuth;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $readOnly = false;
    public int $ppf = 0;

    public string $auto = '';
    public string $plant = '';
    public string $InspectDates = '';
    public string $details = '';
    public string $inspection_group = '';
    public string $username = '';

    public function mount(int $ppf, bool $readOnly = false)
    {
        $this->username = UserAuth::user()->name ?? '';
        $this->InspectDates = now()->format('Y-m-d');
        $this->ppf = $ppf;
        $this->readOnly = $readOnly;

        if($ppf > 0){
            $this->fetchFooterDetails($ppf);
        }
    }


    private function fetchFooterDetails(int $ppf){
        $data = app(InitialDataService::class)->loadfooterDetails($ppf);

        $this->auto = $data['autoMachine'];
        $this->plant = $data['plant'];
        $this->InspectDates = $data['inspectionDate'];
        $this->details = $data['details'];
        $this->inspection_group = $data['inspectionGroup'];
        $this->username = $data['encoder'];
    }

    public function updated($propertyName)
    {
        $this->syncToParent();
    }

    protected function syncToParent(): void
    {
        if (!$this->ppf) return;

        app(DraftService::class)->syncComponent((string) $this->ppf, 'footerDetails', [
            'auto'             => $this->auto,
            'plant'            => $this->plant,
            'InspectDates'     => $this->InspectDates ?? Carbon::now()->format('Y-m-d'),
            'details'          => $this->details,
            'inspection_group' => $this->inspection_group,
        ]);
    }
};
?>
<div id="OuterPanel" class="outer-panel bg-white shadow-lg px-3 py-4">
    <div class=" flex flex-row gap-2 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full mx-auto">
            <label for="automach" class="block text-sm font-medium text-gray-700">
                Auto Machine
            </label>

            <select id="automach"
                class="mt-1 block w-full border border-black rounded-md px-2 py-1
        @if($readOnly)  cursor-not-allowed @endif"
                @if($readOnly) disabled @endif
                wire:model.live.debounce.500ms="auto"
                required>
                <option value="">-- Select Auto Machine --</option>
                <option value="Vario 1">Vario 1</option>
                <option value="Vario 2">Vario 2</option>
                <option value="Vario 4">Vario 4</option>
                <option value="Assy 1">Assy 1</option>
                <option value="Assy 2">Assy 2</option>
            </select>
        </div>

        <div class="w-full mx-auto">
            <label for="plant" class="block text-sm font-medium text-gray-700">
                Plant
            </label>

            <select id="plant"
                class="mt-1 block w-full border border-black rounded-md px-2 py-1
        @if($readOnly)  cursor-not-allowed @endif"
                @if($readOnly) disabled @endif
                wire:model.live.debounce.500ms="plant"
                required>
                <option value="">-- Select Plant--</option>
                <option value="P1A">P1A</option>
                <option value="P1B">P1B</option>
                <option value="P2A">P2A</option>
                <option value="P2B">P2B</option>
            </select>
        </div>
    </div>

    <div class=" flex flex-row gap-2 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full mx-auto">
            <label for="inspectDate" class="block text-sm font-medium text-gray-700 text-center">Inspection Date</label>
            <input type="text" id="inspectDate"
                class="text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder="Select a date"
                required
                wire:model.live.debounce.500ms="InspectDates">
        </div>
        <div class="w-full mx-auto">
            <label for="upd" class="block text-sm font-medium text-gray-700 text-center">Update Date</label>
            <input type="text" id="upd" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly)  cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " value="{{ now()->format('Y-m-d') }}" required readonly>
        </div>
    </div>

    <div class=" flex flex-row gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="details" class="block text-sm font-medium text-gray-700">Details</label>
            <input type="text" id="details" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly)  cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " value="" required wire:model.live.debounce.500ms="details">
        </div>
    </div>

    <div class=" flex flex-row gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="inspection_group" class="block text-sm font-medium text-gray-700">Inspection Group</label>
            <input type="text" id="inspection_group" class=" text-center mt-1 block w-full border border-black rounded-md px-2 py-1 @if($readOnly)  cursor-not-allowed @endif" @if($readOnly) readonly @endif
                placeholder=" " value="" required wire:model.live.debounce.500ms="inspection_group">
        </div>
    </div>

    <div class=" flex flex-col gap-4 mt-4 items-center mx-6 sm:mx-2">
        <div class="w-full">
            <label for="registrant" class="block text-sm font-medium text-gray-700">Registrant</label>
            <input type="text" id="registrant" class="text-center mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required readonly wire:model.live.debounce.500ms="username">
        </div>
    </div>
</div>