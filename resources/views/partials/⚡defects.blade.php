<?php

use App\Models\Defects as ModelsDefects;
use App\Services\Defect\DefectService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public Collection $Largedefects;
    public Collection $SmallDefectsForModal;
    public array $smallDefects = [];
    public array $defects = [];

    public string $newDefect = '';
    public $newQuan;
    public $newCategory;
    public string $newSmallDefect = '';
    public string $newSmallQuan = '';

    public $TotalNg = 0;
    public $TotalSmallQuan = 0;
    public $lastTotalNg = 0;

    public $selectedLargeDefect = null;
    public $editingType;
    public $editingTypeSmall;
    public $currentInspectorId;
    public $currentOperatorSmall;

    public bool $readOnly = false;
    public bool $locked = false;
    public string $systemname = 'GL'; // 'GL' | 'ProcessRecord' | 'operator'
    public $formId;
    public $dispatchPrefix;
    public $ppf = 0;

    // --- GL-only: staged multi-add modal state ---
    public array $stagedDefects = [];
    public $modalSelectedLargeDefect = null;
    public $modalLargeQty = '';
    public array $modalSmallDefects = [];

    public $listeners = [
        'FetchDefect' => 'fetchDefectFromEvent', // operator path
        'locked'      => 'locked',
        'ClearForm'   => 'ClearForm',
    ];

    public $rules = [
        'newDefect' => 'required|string|max:255',
        'newQuan'   => 'required|numeric|min:1',
    ];
    public $messages = [
        'newDefect.required' => 'Please enter a defect type.',
        'newQuan.required'   => 'Please enter a quantity.',
        'newQuan.min'        => 'The quantity must be at least 1.',
    ];

    public function mount($systemname = 'GL', $dispatchPrefix = null, $formId = null, $loadedDefects = [], $loadedSmallDefects = [])
    {
        $this->systemname      = $systemname;
        $this->formId          = $formId;
        $this->dispatchPrefix  = $dispatchPrefix;
        $this->Largedefects    = ModelsDefects::select('LargeDefect')
            ->distinct()->whereNotNull('LargeDefect')->orderBy('LargeDefect', 'ASC')->get();
        $this->SmallDefectsForModal = collect();
        $this->setDefects($loadedDefects);
        $this->setSmallDefects($loadedSmallDefects);
    }

    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm()
    {
        $this->defects        = [];
        $this->smallDefects   = [];
        $this->TotalNg        = 0;
        $this->TotalSmallQuan = 0;
    }

    public function setDefects($defects)
    {
        $this->defects = $defects ?? [];
        $this->TotalNg = collect($this->defects)->sum('qty');
    }

    public function setSmallDefects($smallDefects)
    {
        $this->smallDefects = $smallDefects ?? [];
    }

    // =========================================================================
    // GL / ProcessRecord path — staged multi-add, flat smallDefects[type]
    // =========================================================================

    #[On('checked-ppf')]
    public function checkedPPF($ppf)
    {
        $this->ppf = $ppf;
        $this->fetchDefectByPpf($ppf);
    }

    public function fetchDefectByPpf($ppf)
    {
        $data = app(DefectService::class)->loadDefectsGL($ppf);
        $this->defects = $data['defects'] ?? [];

        $collection = collect($data['smallDefects'] ?? [])
            ->mapWithKeys(fn($items, $large) => [
                $large => collect($items)->map(fn($item) => [
                    'type' => $item['type'] ?? null,
                    'qty'  => $item['qty'] ?? '',
                ])->toArray(),
            ]);

        $this->smallDefects = $collection->toArray();

        $lastLarge = array_key_last($this->smallDefects);
        $this->selectedLargeDefect = $lastLarge;

        $group     = $this->smallDefects[$lastLarge] ?? [];
        $lastSmall = end($group);
        $this->newSmallDefect = $lastSmall['type'] ?? '';
        $this->newSmallQuan   = $lastSmall['qty']  ?? '';

        $this->TotalNg        = collect($this->defects)->sum('qty');
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum(fn($d) => (int)($d['qty'] ?? 0));
    }

    public function loadExistingDefectsToStage(): void
    {
        $this->stagedDefects = app(DefectService::class)
            ->buildStagedListFromDefects($this->defects, $this->smallDefects);
    }

    public function selectLargeDefectInModal(string $largeDefect): void
    {
        if ($this->modalSelectedLargeDefect !== null && $this->modalSelectedLargeDefect !== $largeDefect) {
            $this->stageCurrentEntry();
        }

        if ($this->modalSelectedLargeDefect === $largeDefect) {
            $this->modalSelectedLargeDefect = null;
            $this->modalLargeQty = '';
            $this->modalSmallDefects = [];
            return;
        }

        $this->modalSelectedLargeDefect = $largeDefect;

        $smalls = ModelsDefects::query()
            ->select('SmallDefect')->distinct()->whereNotNull('SmallDefect')
            ->where('LargeDefect', $largeDefect)->orderBy('SmallDefect', 'asc')->get();

        $this->modalSmallDefects = $smalls->map(fn($s) => ['type' => $s->SmallDefect, 'qty' => ''])
            ->values()->toArray();

        $result = app(DefectService::class)->prefillModalSmallDefects(
            $this->modalSmallDefects,
            $this->stagedDefects,
            $largeDefect,
            $this->defects,
            $this->smallDefects
        );

        $this->modalLargeQty     = $result['modalLargeQty'];
        $this->modalSmallDefects = $result['modalSmallDefects'];
    }

    private function stageCurrentEntry(): bool
    {
        if ($this->modalSelectedLargeDefect === null) return true;

        $largeQty = (int) $this->modalLargeQty;
        if ($largeQty < 1) return false;

        $smallsWithQty = app(DefectService::class)->computeSmallsWithQty($this->modalSmallDefects);
        if (collect($smallsWithQty)->sum('qty') > $largeQty) return false;

        $this->stagedDefects = app(DefectService::class)
            ->upsertStagedEntry($this->stagedDefects, $this->modalSelectedLargeDefect, $largeQty, $smallsWithQty);

        return true;
    }

    public function stageDefect(): void
    {
        $this->validate([
            'modalSelectedLargeDefect' => 'required|string',
            'modalLargeQty'            => 'required|numeric|min:1',
        ]);

        $largeQty      = (int) $this->modalLargeQty;
        $smallsWithQty = app(DefectService::class)->computeSmallsWithQty($this->modalSmallDefects);

        if (collect($smallsWithQty)->sum('qty') > $largeQty) {
            $this->addError('modalSmallDefects', 'Total small qty cannot exceed large qty.');
            return;
        }

        $this->stagedDefects = app(DefectService::class)
            ->upsertStagedEntry($this->stagedDefects, $this->modalSelectedLargeDefect, $largeQty, $smallsWithQty);

        $this->modalSelectedLargeDefect = null;
        $this->modalLargeQty = '';
        $this->modalSmallDefects = [];
        $this->resetErrorBag();
    }

    public function removeStagedDefect(string $type): void
    {
        $this->stagedDefects = app(DefectService::class)->removeStagedEntry($this->stagedDefects, $type);
    }

    public function resetModalState(): void
    {
        $this->dispatch('disregardStaged');
    }

    #[On('confirmedDisregard')]
    public function resetStaged()
    {
        $this->modalSelectedLargeDefect = null;
        $this->modalLargeQty = '';
        $this->modalSmallDefects = [];
        $this->stagedDefects = [];
        $this->resetErrorBag();
        $this->dispatch('close-add-defect');
    }

    public function addDefectFromModal(): void
    {
        if ($this->modalSelectedLargeDefect !== null && (int) $this->modalLargeQty >= 1) {
            $this->stageCurrentEntry();
        }

        if (empty($this->stagedDefects)) {
            $this->validate([
                'modalSelectedLargeDefect' => 'required|string',
                'modalLargeQty'            => 'required|numeric|min:1',
            ]);
            return;
        }

        $result = app(DefectService::class)
            ->mergeStagedIntoDefects($this->stagedDefects, $this->defects, $this->smallDefects, $this->Largedefects);

        $this->defects      = $result['defects'];
        $this->smallDefects = $result['smallDefects'];

        $this->TotalNg        = collect($this->defects)->sum('qty');
        $this->TotalSmallQuan = collect($this->smallDefects)->flatten(1)->sum(fn($d) => (int)($d['qty'] ?? 0));

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => $this->defects, 'smallDefects' => $this->smallDefects,
            'formId' => $this->formId, 'action' => 'add',
        ]);

        $this->sendDefect();
        $this->resetStaged();
    }

    public function updateDefectArray()
    {
        foreach ($this->defects as &$defect) {
            if ($defect['type'] === $this->editingType) {
                $defect['qty'] = (float) $this->newQuan;
                break;
            }
        }
        unset($defect);

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'defects' => [['type' => $this->editingType, 'qty' => $this->newQuan]],
            'formId' => $this->formId, 'action' => 'update',
        ]);

        $this->sendDefect();
        $this->editingType = null;
        $this->newQuan = '';
    }

    public function startEdit($type)
    {
        $this->editingType = $type;
        $defect = collect($this->defects)->firstWhere('type', $type);
        if ($defect) $this->newQuan = $defect['qty'];
    }

    public function startEditSmall($large, $type)
    {
        $this->selectedLargeDefect = $large;
        $this->editingTypeSmall = $type;
        $small = collect($this->smallDefects[$large] ?? [])->firstWhere('type', $type);
        if ($small) $this->newSmallQuan = $small['qty'];
    }

    public function updateDefectSmallArray()
    {
        $large = $this->selectedLargeDefect;
        if (!isset($this->smallDefects[$large])) return;

        foreach ($this->smallDefects[$large] as &$small) {
            if ($small['type'] === $this->editingTypeSmall) {
                $small['qty'] = $this->newSmallQuan;
                break;
            }
        }
        unset($small);

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects' => $this->smallDefects, 'formId' => $this->formId, 'action' => 'update',
        ]);

        $this->editingTypeSmall = null;
        $this->newSmallQuan = '';
    }

    public function deleteDefect($type)
    {
        $this->defects = collect($this->defects)->reject(fn($d) => $d['type'] === $type)->values()->toArray();
        unset($this->smallDefects[$type]);
        $this->TotalNg = collect($this->defects)->sum('qty');
    }

    public function deleteDefectArray($type)
    {
        $this->deleteDefect($type);
        $this->sendDefect();
    }

    public function deleteDefectSmall($largeDefect, $type)
    {
        if (!isset($this->smallDefects[$largeDefect])) return;
        $this->smallDefects[$largeDefect] = collect($this->smallDefects[$largeDefect])
            ->reject(fn($d) => trim($d['type'] ?? '') === trim($type))->values()->toArray();

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'smallDefects' => [$largeDefect => [['type' => $type, 'qty' => 0]]],
            'formId' => $this->formId, 'action' => 'delete',
        ]);
    }

    // =========================================================================
    // Operator path — live per-inspector/process fetch, nested smallDefects
    // =========================================================================

    public function fetchDefectFromEvent($data)
    {
        $this->defects = $data['defects'] ?? [];
        $this->smallDefects = [];

        foreach ($data['smallDefects'] ?? [] as $largeDefect => $encodeGroups) {
            foreach ($encodeGroups as $encodeProcess => $inspectors) {
                $processKey = strtolower(trim($encodeProcess));
                foreach ($inspectors as $inspectorId => $smallList) {
                    $inspectorKey = (string) $inspectorId;
                    foreach ($smallList as $small) {
                        $this->smallDefects[$largeDefect][$processKey][$inspectorKey][] = [
                            'type'       => $small['type'] ?? null,
                            'qty'        => (int) ($small['qty'] ?? 0),
                            'operatorid' => $inspectorId,
                        ];
                    }
                }
            }
        }

        $lastDefect = end($this->defects);
        $this->selectedLargeDefect = $lastDefect['type'] ?? null;

        if ($lastDefect) {
            $operatorId    = (string) ($lastDefect['operatorid'] ?? '');
            $encodeProcess = strtolower(trim($lastDefect['Process'] ?? ''));
            $largeDefect   = $lastDefect['type'] ?? null;

            $group     = $this->smallDefects[$largeDefect][$encodeProcess][$operatorId] ?? [];
            $lastSmall = end($group);

            $this->newSmallDefect = $lastSmall['type'] ?? '';
            $this->newSmallQuan   = $lastSmall['qty']  ?? '';
        }

        $this->TotalNg = collect($this->defects)->sum(fn($d) => (int) $d['qty']);
    }

    public function deleteDefectOperator($operator, $type)
    {
        $this->defects = collect($this->defects)
            ->reject(fn($d) => $d['type'] === $type && ($d['operatorid'] ?? null) == $operator)
            ->values()->toArray();

        if (isset($this->smallDefects[$type][$operator])) {
            unset($this->smallDefects[$type][$operator]);
            if (empty($this->smallDefects[$type])) unset($this->smallDefects[$type]);
        }

        $this->TotalNg = collect($this->defects)->sum('qty');
    }

    public function startEditOperator($operator, $type)
    {
        $this->editingType = $type;
        $this->currentInspectorId = $operator;

        $defect = collect($this->defects)
            ->first(fn($d) => $d['type'] === $type && ($d['operatorid'] ?? null) == $operator);

        $this->newQuan = $defect['qty'] ?? 0;
    }

    /**
     * FIXED: was reading smallDefects[$large][$operator][$smallType] as an
     * associative lookup — but fetchDefectFromEvent() writes a LIST under
     * [$large][$process][$operator]. Now searches that list by type.
     */
    public function startEditSmallOperator($largeDefect, $process, $operator, $smallType)
    {
        $this->selectedLargeDefect = $largeDefect;
        $this->currentOperatorSmall = $operator;
        $this->editingTypeSmall = $smallType;

        $group = $this->smallDefects[$largeDefect][$process][$operator] ?? [];
        $small = collect($group)->firstWhere('type', $smallType);

        if ($small) $this->newSmallQuan = $small['qty'];
    }

    public function updateDefectSmallArrayOperator($process)
    {
        $large    = $this->selectedLargeDefect;
        $operator = $this->currentOperatorSmall;

        if (!isset($this->smallDefects[$large][$process][$operator])) return;

        foreach ($this->smallDefects[$large][$process][$operator] as &$small) {
            if ($small['type'] === $this->editingTypeSmall) {
                $small['qty'] = $this->newSmallQuan;
                break;
            }
        }
        unset($small);

        $this->dispatch('FromSmallDefects', smalldefectData: [
            'SelectedLargeDefect' => $large, 'Operator' => $operator,
            'type' => $this->editingTypeSmall, 'qty' => $this->newSmallQuan, 'action' => 'update',
        ]);

        $this->editingTypeSmall = null;
        $this->newSmallQuan = '';
        $this->currentOperatorSmall = null;
    }

    public function deleteDefectSmallOperator($process, $operator, $type)
    {
        $large = $this->selectedLargeDefect;
        if (!isset($this->smallDefects[$large][$process][$operator])) return;

        $this->smallDefects[$large][$process][$operator] = collect($this->smallDefects[$large][$process][$operator])
            ->reject(fn($d) => $d['type'] === $type)->values()->toArray();

        if (empty($this->smallDefects[$large][$process][$operator])) {
            unset($this->smallDefects[$large][$process][$operator]);
        }

        $this->dispatch('FromSmallDefects', smalldefectData: [
            'SelectedLargeDefect' => $large, 'Operator' => $operator,
            'newSmallDefect' => $type, 'action' => 'delete',
        ]);

        $this->newSmallDefect = '';
        $this->newSmallQuan = '';
        $this->currentOperatorSmall = null;
    }

    // =========================================================================
    // Shared
    // =========================================================================

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function sendDefect()
    {
        $this->dispatch('sendNg', $this->TotalNg);
    }
};
?>

<div x-data="{ openAddDefect: false }" @close-add-defect.window="openAddDefect = false" class="bg-white px-3 py-4 rounded-lg w-full max-w-1xl @if($locked) opacity-50 cursor-not-allowed @endif">
    <div class="bg-white shadow-md max-w-3xl mx-auto px-3 py-4 rounded-lg h-[500px] flex flex-col">
        <div class="bg-gray-700 w-full">
            <p class="text-4xl font-extrabold text-center text-white p-4">Defect</p>
        </div>

        @if($systemname !== 'operator')
        <div class="w-full flex justify-center mb-3 px-3 mt-5">
            <button
                @click="$wire.loadExistingDefectsToStage().then(() => { openAddDefect = true })"
                class="text-white w-11/12 sm:w-2/3 bg-[#0F3C89] hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                @if($readOnly) disabled @endif id="add-defect">
                Add Defect / Edit Staged Defects
            </button>
        </div>
        @endif

        @if($systemname !== 'operator')
            <x-ui.defect.operator-table :defects="$defects" :small-defects="$smallDefects" :readOnly="$readOnly" />
        @else
            <x-ui.defect.defects-table :defects="$defects" :small-defects="$smallDefects" :systemname="$systemname" :readOnly="$readOnly" />
        @endif

        <div class="flex-col w-11/12 sm:w-1/3 mt-3">
            <label for="TotalNG" class="block text-sm font-medium text-black">Total NG</label>
            <input type="text" id="TotalNG" class="my-2 block w-full border border-black rounded-md px-2 py-1"
                placeholder=" " required wire:model="TotalNg" readonly>
        </div>

        @error('newDefect') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        @error('newQuan') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        @error('newSmallQuan') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
        @error('newSmallDefect') <p class="text-red-500 text-sm">{{ $message }}</p> @enderror
    </div>

    @if($systemname !== 'operator')
    <x-ui.defect.add-defect-modal
        :staged-defects="$stagedDefects"
        :modal-selected-large-defect="$modalSelectedLargeDefect"
        :modal-large-qty="$modalLargeQty"
        :modal-small-defects="$modalSmallDefects"
        :large-defects="$Largedefects"
        :readOnly="$readOnly" />
    @endif
</div>