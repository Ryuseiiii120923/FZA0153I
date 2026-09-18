<?php

use App\Repositories\Rework\ReworkRepository;
use App\Services\Rework\ReworkService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public Collection $reworkcheck;
    public array $reworkss = [];
    public string $newRework = '';
    public $newQuan = '';
    public array $totalngrework = [];
    public array $hfname = [];
    public $editingType;
    public $editinghfno;
    public bool $locked = false;
    public $formId;
    public $dispatchPrefix;
    public string $systemname = 'GL';
    public int $ppf = 0;

    public array $hfno = [];
    public array $totalInsp = [];
    public array $stagedReworks = [];

    public $rules = [
        'newRework' => 'required|string|max:255',
        'newQuan'   => 'required|numeric|min:1',
    ];

    protected $messages = [
        'newRework.required' => 'Please enter a rework type.',
        'newQuan.required'   => 'Please enter a quantity.',
    ];

    public function mount($systemname = 'GL', $dispatchPrefix = null, $formId = null, $loadedRework = [])
    {
        $this->systemname     = $systemname;
        $this->formId         = $formId ?? 'default';
        $this->dispatchPrefix = $dispatchPrefix;
        $this->reworkcheck    = app(ReworkRepository::class)->getReworkTypes();
        $this->setReworks($loadedRework);
    }

    #[On('checked-ppf')]
    public function checkedPpf($ppf){
        $this->ppf = $ppf;

        $this->Fetch($ppf);
    }

    #[On('FetchHfNo')]
    public function fetchhfno($hf_id, $total_inspect, $form_id)
    {
        $this->hfno[$form_id]      = $hf_id;
        $this->totalInsp[$form_id] = (int) $total_inspect;
    }

    public function setReworks($loadedReworks)
    {
        $this->reworkss = $loadedReworks;
        $this->totalngrework = [];

        foreach ($loadedReworks as $rework) {
            $formId = $rework['formId'] ?? $this->formId;
            $this->totalngrework[$formId] = ($this->totalngrework[$formId] ?? 0) + (int) ($rework['quan'] ?? 0);
        }
    }

    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm($formId = null)
    {
        $formId ??= $this->formId;

        $this->reworkss = collect($this->reworkss)
            ->reject(fn($r) => ($r['formId'] ?? $formId) === $formId)
            ->values()
            ->toArray();

        $this->totalngrework[$formId] = 0;
    }

    public function Fetch($ppf)
    {
        $data = app(ReworkService::class)->getReworks($ppf);
        $formId = $data['formId'] ?? $this->formId;

        $formReworks = collect($data['reworks'] ?? [])
            ->filter(fn($r) => ($r['formId'] ?? $formId) === $formId)
            ->values()
            ->toArray();

        $this->reworkss = $formReworks;
        $this->totalngrework[$formId] = app(ReworkService::class)->totalForForm($formReworks);

        $this->dispatch($this->dispatchPrefix . '.FetchNgDropdown', [
            'formId'        => $formId,
            'totalReworkNg' => $this->totalngrework[$formId],
        ]);
    }

    public function stageRework()
    {
        $this->validate();

        $service = app(ReworkService::class);

        if (!$service->existsInMasterList($this->reworkcheck, $this->newRework)) {
            $this->addError('newRework', 'This rework defect does not exist in the master list');
            return;
        }

        $currentHfNo = $this->hfno[$this->formId] ?? '';
        if ($currentHfNo === '') {
            $this->addError('hfno', 'Please enter HF number.');
            return;
        }

        if (empty($this->totalInsp[$this->formId])) {
            $this->addError('totalInsp', 'Please enter total inspections.');
            return;
        }

        if ($service->isDuplicate($this->reworkss, $this->stagedReworks, $currentHfNo, $this->newRework)) {
            $this->addError('newRework', 'This rework type is already added');
            return;
        }

        if ($service->hfLimitReached($this->reworkss, $this->stagedReworks, $currentHfNo)) {
            $this->addError('hfno', 'You can only add up to 5 HF Numbers');
            return;
        }

        $this->stagedReworks[] = $service->buildStagedEntry(
            $currentHfNo,
            $this->totalInsp[$this->formId] ?? '',
            $this->newRework,
            $this->newQuan
        );

        $this->newRework = '';
        $this->newQuan   = '';
        $this->resetErrorBag();
    }

    public function removeStagedRework(int $index): void
    {
        array_splice($this->stagedReworks, $index, 1);
    }

    public function resetReworkModal(): void
    {
        if (isset($this->stagedReworks)) {
            $this->dispatch('disregardStaged');
        }
    }

    #[On('confirmedDisregard')]
    public function resetStaged()
    {
        $this->stagedReworks = [];
        $this->newRework     = '';
        $this->newQuan       = '';
        $this->resetErrorBag();
        $this->dispatch('close-add-rework');
    }

    public function addRework()
    {
        if (empty($this->stagedReworks) && !empty($this->newRework) && !empty($this->newQuan)) {
            $this->stageRework();
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        if (empty($this->stagedReworks)) {
            $this->addError('newRework', 'Please stage at least one rework entry before confirming.');
            return;
        }

        $this->reworkss = app(ReworkService::class)
            ->mergeStagedIntoCommitted($this->stagedReworks, $this->reworkss);

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => $this->reworkss,
            'formId'      => $this->formId,
            'action'      => 'add',
        ]);

        $this->UpdatedNgRework($this->formId);
        $this->resetStaged();
    }

    public function CheckHf()
    {
        try {
            if (empty($this->hfno[$this->formId] ?? null)) {
                $this->hfname[$this->formId] = null;
                $this->resetErrorBag('hfno');
                return;
            }

            $repo = app(ReworkRepository::class);
            $code = $this->hfno[$this->formId];
            $searchValue = (strlen($code) === 2) ? ' ' . $code : $code;

            $hf = $repo->findWorkerByCode($searchValue);

            if ($hf) {
                $name = $repo->findWorkerName($hf->社員CD);
                $this->hfname[$this->formId] = $name ? $name->名前 : null;
                $this->resetErrorBag('hfno');
            } else {
                $this->addError('hfno', 'This Operator does not exist');
                $this->hfno[$this->formId]   = "";
                $this->hfname[$this->formId] = null;
            }
        } catch (\Exception $e) {
            $this->addError('hfno', 'An error occurred while validating the HF number');
            Log::error('Error validating HF number: ' . $e->getMessage());
            $this->hfno[$this->formId]   = "";
            $this->hfname[$this->formId] = null;
        }
    }

    public function deleteRework($hfno, $type)
    {
        $this->reworkss = app(ReworkService::class)
            ->removeByHfnoAndType($this->reworkss, $hfno, $type);

        $cleanHfno = trim($hfno);
        $cleanType = trim(strtoupper($type));

        $this->dispatch('NeedToDeleteRework', [
            'hfno'   => $cleanHfno,
            'type'   => $cleanType,
            'formId' => $this->formId,
        ]);

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => [['hfno' => $cleanHfno, 'type' => $cleanType]],
            'formId'      => $this->formId,
            'action'      => 'delete',
        ]);

        $this->UpdatedNgRework($this->formId);
    }

    public function UpdatedNgRework($formId = null)
    {
        $formId = $formId ?? $this->formId;
        if (!$formId) return;

        $this->totalngrework[$formId] = app(ReworkService::class)
            ->totalForForm($this->reworkss, $this->hfno[$formId] ?? null);

        $this->dispatch($this->dispatchPrefix . '.FetchNgReworkDropdown', [
            'formId'        => $formId,
            'totalReworkNg' => $this->totalngrework[$formId],
        ]);
    }

    public function updateRework()
    {
        $this->reworkss = app(ReworkService::class)->updateEntry(
            $this->reworkss,
            $this->editingType,
            $this->editinghfno,
            $this->newQuan,
            $this->hfno[$this->formId] ?? null,
            $this->totalInsp[$this->formId] ?? null
        );

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => [[
                'hfno'      => $this->hfno[$this->formId] ?? null,
                'type'      => $this->editingType,
                'quan'      => $this->newQuan,
                'totalinsp' => $this->totalInsp[$this->formId] ?? null,
            ]],
            'formId' => $this->formId,
            'action' => 'update',
        ]);

        $this->editingType = null;
        $this->newQuan     = '';
        $this->totalInsp[$this->formId] = '';
    }

    public function startEditRework($formId, $type, $hfno)
    {
        $this->editingType = $type;
        $this->editinghfno = $hfno;
        $this->formId       = $formId;

        $rework = collect($this->reworkss)
            ->first(fn($r) => ($r['type'] ?? '') === $type && ($r['hfno'] ?? '') == $hfno);

        if ($rework) {
            $this->hfno[$formId]      = $rework['hfno'];
            $this->newQuan            = $rework['quan'];
            $this->totalInsp[$formId] = $rework['totalinsp'];
        }
    }

    public function loadExistingStagedRework()
    {
        $this->stagedReworks = app(ReworkService::class)
            ->buildStagedListFromCommitted($this->reworkss);
    }
};
?>

<div x-data="{ openAddRework: false }" @close-add-rework.window="openAddRework = false" class="bg-white rounded-lg w-full max-w-1xl mx-auto py-4 @if($locked) opacity-50 cursor-not-allowed @endif">
    <div class="bg-gray-700 w-full">
        <p class="text-4xl font-extrabold text-center text-white p-4">Rework</p>
    </div>

    @if($systemname !== 'operator')
    <div class="w-full flex flex-col items-center mt-5">
        <button @click="$wire.loadExistingStagedRework().then(() => { openAddRework = true; $nextTick(() => $refs.firstInput?.focus()); })"
            class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
            type="button" id="add-rework" @if($locked) disabled @endif>
            Add Rework / Edit Staged Rework
        </button>
    </div>
    @endif

    <x-ui.rework.rework-table
        :reworkss="$reworkss"
        :system-name="$systemname"
        :form-id="$formId"
        :locked="$locked" />

    <div class="flex-col w-11/12 sm:w-1/3 mx-5 sm:mx-2 mt-3">
        <label for="HfNo" class="block text-sm font-medium text-black">Total NG Rework</label>
        <input type="text" id="TotalNgRework" class="my-2 block w-full border border-black rounded-md px-2 py-1"
            placeholder="" required wire:model="totalngrework.{{ $formId }}" readonly>
    </div>

    @error('hfno.' . $formId) <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('totalInsp.' . $formId) <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newRework') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
    @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror

    @if($systemname !== 'operator')
    <x-ui.rework.add-rework-modal
        :staged-reworks="$stagedReworks"
        :form-id="$formId"
        :hfno="$hfno"
        :hfname="$hfname"
        :total-insp="$totalInsp"
        :rework="$reworkcheck"
        :reworkss="$reworkss"
        :locked="$locked" />
    @endif
</div>