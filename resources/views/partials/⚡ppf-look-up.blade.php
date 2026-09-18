<?php // resources/views/components/checkppf.blade.php

use App\Models\Inspector\PRInsp;
use App\Models\Worker;
use App\Services\General\InitialDataService;
use App\Services\General\PPFService;
use App\Services\PrencodeService;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;

new class extends Component
{
    public string|null $ppf, $lotno, $partno, $matno, $moldno, $pressno, $shift, $opt, $expct;
    public string|null $errorexisting;
    public array $defects = [];
    public array $smalldefects = [];

    public string $lastdef;
    public string $lastqty;
    public string $action = "";
    public string $method;
    public string $totalInspection;
    public string|null $encoder = "", $inspectorID = "";
    public string|null $progressInsp;

    public bool $isAccept = false;
    public bool $canEditTotal = false;
    public bool $readOnly = false;
    public bool $ppfLoaded = false;
    public bool $showInspectionModal = false; // controls the modal
    public bool $isPPF = false;
    public $OperatedinspectorID;

    public bool $alreadyEnter = false;

    public string|null $systemname = null;
    public string|null $actiondash = null;

    public $rules = ['ppf' => 'required|numeric'];
    public  $messages = [
        'ppf.required' => 'Please enter ppf',
        'ppf.integer' => 'PPF must be integer'
    ];
    #[On('SystemName')]
    public function SystemName(array|null $data)
    {
        $this->systemname = $data['systemname'] ?? null;
    }

    #[On('locked')]
    public function locked(bool $data)
    {
        $this->readOnly = $data;
    }

    #[On('Views')]
    public function Views(array $data)
    {
        $this->ppf = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->matno = $data['matno'];
    }

    #[On('fromppf')]
    public function fromppf(array|null $data)
    {
        $this->ppf = $data;
        $this->checkPPF();
    }

    #[On('action-changed')]
    public function actionChanged($action){
        $this->action = $action;
    }

    #[On('AcceptTotal')]
    public function AcceptTotal()
    {
        $variance = app(PPFService::class)->calculateQtyVariance($this->totalInspection, $this->expct);

        $this->dispatch('UpdateQty', [
            'excssqty' => $variance['excess'],
            'lackqty'  => $variance['lack'],
        ]);

        $this->isPPF = false; //disable the progress
        $this->dispatch('IsAdd');
    }

    #[On('RejectTotal')]
    public function RejectTotal()
    {
        $variance = app(PPFService::class)->calculateQtyVariance($this->totalInspection, $this->expct);

        $this->dispatch('rejectQty', [
            'excssqty' => $variance['excess'],
            'lackqty'  => $variance['lack'],
        ]);
        $this->dispatch('IsAdd');
    }

    #[On('errorExisting')]
    public function hasError(string|null $data)
    {
        $this->errorexisting = $data;
    }

    public function totalInspectedProgress()
    {
        $totalInspected = PRInsp::where('ppfno', $this->ppf)->where('operation', 'VI')
            ->sum('total_inspect');
        if ((string) $totalInspected === (string) $this->totalInspection) {
            return;
        }
        $this->totalInspection = (string) $totalInspected;
        $this->progressInsp = $this->totalInspection . "/" . $this->expct;

        if ((int)$this->totalInspection === (int)$this->expct) {
            $this->isAccept = true;
            $this->dispatch('UpdateQty', [
                'excssqty' => 0,
                'lackqty' => 0,
            ]);
            $this->dispatch('GoodNg');
        } else {
            $this->isAccept = false;
        }
    }

    #[On('totalInspectedProgress')]
    public function totalInspectedProgressFetch()
    {
        $totalInspected = PRInsp::where('ppfno', $this->ppf)->where('operation', 'VI')
            ->sum('total_inspect');

        $this->totalInspection = (string) $totalInspected;

        $this->progressInsp = $this->totalInspection . "/" . $this->expct;
    }

    private function loadMainData()
    {

        $data = app(InitialDataService::class)->loadPPfData($this->ppf, $this->actiondash);
        if (isset($data['error'])) {
            $this->errorexisting = $data['error'];
            return;
        }

        if ($this->actiondash == 'view') {
            $this->dispatch('readOnly');
        }
        $this->showInspectionModal = $data['showModal'] ?? false;

        $this->lotno   = $data['lotno'] ?? "";
        $this->partno  = $data['partno'] ?? "";
        $this->matno   = $data['matno'] ?? "";
        $this->moldno  = $data['moldno'] ?? "";
        $this->pressno = $data['pressno'] ?? "";
        $this->shift   = $data['shift'] ?? "";
        $this->opt     = $data['opt'] ?? "";
        $this->expct   = $data['expct'] ?? 0;
        $this->totalInspection = $data['totalInspection'] ?? 0;
        $this->dispatch('fetchTotalInspection', $this->totalInspection);
        $this->dispatch('sendExcpt', $this->expct);
        if ($this->actiondash != 'edit') {
            if ($this->systemname === 'ProcessRecord') {
                $this->showInspectionModal = true;
            }
        }
        $this->totalInspectedProgress();
        return true;
    }

    public function mount(string|null $ppf = null, string|null $operatedID = null, string|null $actiondash = null)
    {
        $this->ppf = $ppf;
        $this->actiondash = $actiondash ?? 'add';
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $this->inspectorID = Worker::where('社員CD', $this->encoder)
            ->value('作業員CD');
        $this->OperatedinspectorID = $operatedID;
    }

    #[On('ClearForm')]
    public function ClearForm()
    {

        $this->ppf =  null;
        $this->lotno =  null;
        $this->partno =  null;
        $this->matno =  null;
        $this->moldno =  null;
        $this->pressno =  null;
        $this->shift = null;
        $this->opt = null;
        $this->expct = 0;
        $this->progressInsp = null;
    }

    #[On('ProgDis')]
    public function ProgDis()
    {
        $this->isPPF = false;
    }

    private function handleGlAdd(string $ppf)
    {
        $ppfService = app(PPFService::class);

        if (!$ppfService->isFifoExempt($this->partno)) {
            $result = app(InitialDataService::class)->checkFifo($this->partno, $this->lotno);

            if (!$result['fifo']) {
                // Hand off to the FifoAuthModal component — it owns the
                // dialog's fields/validation and dispatches
                // 'FifoOverrideConfirmed' back once authorized.
                $this->dispatch(
                    'open-fifo-auth',
                    ppf: $ppf,
                    authMessage: "Lot {$result['oldestLot']} should be encoded first. Supervisor authorization is required to proceed."
                );
                return;
            }
        }

        $this->proceedWithAdd($ppf);
    }

    private function proceedWithAdd(string $ppf)
    {
        $this->isPPF = true; //enable the inspection Progress
        $this->dispatch('LoadDefectsGL', $ppf);
        $this->dispatch('LoadReworksGL', $ppf);
        $this->dispatch('FetchDoneRework', $ppf);
        $this->dispatch('fetchForRework', $ppf);
    }

    #[On('FifoOverrideConfirmed')]
    public function onFifoOverrideConfirmed(array $data)
    {
        $this->proceedWithAdd($data['ppf']);
    }

    #[On('post-ppf')]
    public function checkPPF()
    {
        $this->validate();
        if (empty($this->ppf)) {
            $this->dispatch('ppf-error');
            return;
        }
        $this->loadMainData();
        $this->dispatch('checked-ppf', $this->ppf);
    }

};
?>

<div class="bg-white shadow-lg px-3 py-4 rounded-lg">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-4">

        <!-- PPF Input -->
        <div class="flex flex-col mx-5 sm:mx-2">
            <label for="PPF" class="block text-md font-medium text-black mb-1">PPF</label>
            <input
                type="number"
                id="PPF"
                class="w-full border border-black rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter PPF"
                wire:model="ppf"
                wire:blur="checkPPF">

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
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 mx-3 ">
        <div class="flex flex-col w-full mx-5 sm:mx-2">
            <label for="PartNo" class="block text-sm font-medium text-black">Part No</label>
            <input type="text" id="PartNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="partno" readonly>
        </div>

        <div class="flex flex-col w-full mx-5 sm:mx-2">
            <label for="LotNo" class="block text-sm font-medium text-black">Lot No.</label>
            <input type="text" id="LotNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="lotno" readonly>
        </div>

        <div class="flex flex-col w-full mx-5 sm:mx-2">
            <label for="MatNo" class="block text-sm font-medium text-black">Material No.</label>
            <input type="text" id="MatNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="matno" readonly>
        </div>

        <div class="flex flex-col w-full mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif">
            <label for="MoldNo" class="block text-sm font-medium text-black">Molding Die No.</label>
            <input type="text" id="MoldNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="moldno" readonly>
        </div>

        <div class="flex flex-col w-full mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif">
            <label for="PressNo" class="block text-sm font-medium text-black">Press No.</label>
            <input type="text" id="PressNo" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="pressno" readonly>
        </div>

        <div class="flex flex-col w-full mx-5 sm:mx-2">
            <label for="shift" class="block text-sm font-medium text-black">Shift</label>
            <input type="text" id="shift" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="shift" readonly>
        </div>

        <div class="flex flex-col w-full mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif">
            <label for="opt" class="block text-sm font-medium text-black">Operator</label>
            <input type="text" id="opt" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="opt" readonly>
        </div>
        <div class="flex flex-col w-full mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif">
            <label for="expct" class="block text-sm font-medium text-black">Expected Qty</label>
            <input type="text" id="expct" class="mt-1 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " value="" required wire:model="expct" required readonly>
        </div>
    </div>
    <div class="flex flex-col mt-3 w-full sm:w-1/3 mx-5 sm:mx-2 @if($systemname == 'ProcessRecord') hidden @endif">

        <!-- Label -->
        <label for="ProgressInsp" class="block text-sm font-medium text-black">Inspection Progress</label>

        <!-- Input + Accept/Reject choice on same line -->
        <div class="flex items-center gap-2 mt-1">
            <input type="text"
                id="ProgressInsp"
                class="flex-1 border border-black rounded-md px-2 py-1 me-4"
                placeholder=" "
                required
                wire:model="progressInsp"
                readonly>

            @if(!$isAccept && $actiondash == 'add')
            <div x-data="{ confirming: false }" class="ms-3">
                <button type="button"
                    @click="confirming = true"
                    x-show="!confirming"
                    class="px-4 py-1 bg-blue-600 text-white rounded-md
                       hover:bg-blue-700 hover:shadow-md transition duration-200">
                    Accept
                </button>

                <div x-show="confirming" x-cloak class="flex items-center gap-2">
                    <span class="text-sm text-gray-700 me-1">Accept or reject this total?</span>
                    <button type="button"
                        wire:click="AcceptTotal"
                        @click="confirming = false"
                        class="px-4 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200">
                        Accept
                    </button>
                    <button type="button"
                        wire:click="RejectTotal"
                        @click="confirming = false"
                        class="px-4 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200">
                        Reject
                    </button>
                    <button type="button"
                        @click="confirming = false"
                        class="px-3 py-1 text-gray-600 hover:text-gray-800 text-sm">
                        Cancel
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <livewire:.ui.modal.fifo-auth-modal />
</div>