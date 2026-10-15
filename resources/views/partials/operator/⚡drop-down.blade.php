<?php

use App\Repositories\Rework\ReworkRepository;
use App\Services\Helpers\DraftService;
use App\Services\Operator\DropdownService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public $forms = [];
    public $toggles = false;
    public $hasError = false;
    public $hasErrorForm = [];
    public $isSaved = false;
    public $inspectorId = '';

    public $activeTab = 'worker';
    public $hf_id = '', $ppf;
    public $total_inspect = '';
    public $finishingProcedure = '';
    public array $modalOpen = [];
    public array $modalMode = [];

    public $expectedQty = 0;
    public $isCheckPPF;
    public $dropdownForms = [];
    public array $reworkNg = [];
    public array $defectNg = [];

    protected DraftService $draft;

    protected const AUTO_METHODS = ['AUTO_DIM', 'AUTO_SF', 'AUTO_PLSF', 'AUTO_NG', 'AUTO_DIM_NG', 'AUTO_SF_NG'];


    public function mount(int $operatorID)
    {
        $this->inspectorId = $operatorID;
    }

    public function boot(DraftService $draft): void
    {
        $this->draft = $draft;
    }

    protected function draftKey(): string
    {
        return (string) $this->inspectorId;
    }

    private function syncDraft(array $forms): void
    {
        $this->draft->syncComponent($this->draftKey(), 'dropdown', [
            'forms' => $forms,
        ]);
    }

    /**
     * Single entry point for every "add new form" button. Replaces
     * addNew()/addNewHF()/addNewPL()/addNewSF()/addNewAutoDimension()/
     * addNewAutoSF()/addNewAutoPLSF()/addNewAutoNG()/addNewAutoDimNG()/
     * addNewAutoSFNG() — each is now a one-line wrapper below so the
     * blade's existing wire:click names keep working unchanged.
     */
    protected function addForm(array $overrides = [], string $seedHfId = ''): string
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();

        $overrides = array_merge(['hf_id' => $seedHfId], $overrides);

        $this->forms[$formId] = app(DropdownService::class)
            ->buildFormTemplate($formId, $overrides);

        $this->modalOpen[$formId] = true;
        $this->dispatch('scroll-to-form', formId: $formId);

        return $formId;
    }

    public function addNew()
    {
        $this->addForm();
    }

    public function addNewHF()
    {
        $this->addForm([], $this->hf_id);
    }

    public function addNewPL()
    {
        $this->addForm(['Operation' => null, 'Process' => null, 'method' => 'PL', 'Remarks' => '']);
    }

    public function addNewSF()
    {
        $this->addForm(['Operation' => null, 'Process' => null, 'method' => 'SF', 'ForRework' => null, 'Remarks' => '']);
    }

    public function addNewDoneRework()
    {
        $result = app(ReworkRepository::class)->fetchFlag((int)$this->ppf);
        $flgDone = $result['FlgDone'] ?? false;
        $proceedToRework = $result['ProceedToRework'] ?? false;

        if ($flgDone && $proceedToRework) {
            $this->addForm(['ForRework' => true, 'Operation' => 'VI', 'Process' => 'SRW']);
            return;
        }

        if (!$proceedToRework) {
            $this->dispatch('errorExisting', 'Please confirm first in For Rework Table in Dashboard.');
            return;
        }

        if (!$flgDone) {
            $this->dispatch('errorExisting', 'Please Encode first in HFRW.');
        }
    }

    public function addNewAutoDimension()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto Dimension Checking', 'method' => 'AUTO_DIM']);
    }

    public function addNewAutoSF()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto SF Inspection', 'method' => 'AUTO_SF']);
    }

    public function addNewAutoPLSF()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto PL/SF Inspection', 'method' => 'AUTO_PLSF']);
    }

    public function addNewAutoNG()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto NG Checking', 'method' => 'AUTO_NG']);
    }

    public function addNewAutoDimNG()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto Dimension of VI Good from NG', 'method' => 'AUTO_DIM_NG']);
    }

    public function addNewAutoSFNG()
    {
        $this->addForm(['Operation' => 'AUTO', 'Process' => 'Auto SF Dimension of VI Good from NG', 'method' => 'AUTO_SF_NG']);
    }

    public function saveRemarks($formId)
    {
        $remarks = $this->forms[$formId]['Remarks'] ?? '';
        $this->forms[$formId]['Remarks'] = trim((string) $remarks);
        $this->receiveDropdownData($this->forms);
    }

    #[On('fetchppf')]
    public function fetchppf($data)
    {
        $this->ppf = $data;
    }

    public function updatedForms()
    {
        $this->syncDraft($this->forms);
    }

    #[On('IsCheckPPF')]
    public function IsCheckPPF($data)
    {
        $this->isCheckPPF = $data;
    }

    public function clearScrollTarget(string $formId): void
    {
        if (isset($this->forms[$formId])) {
            $this->forms[$formId]['scroll_target'] = false;
        }
    }

    #[On('expected')]
    public function expectedQty($data)
    {
        $this->expectedQty = $data;
    }

    public function saveHF($formId)
    {
        try {
            $this->forms = app(DropdownService::class)->saveHF(
                $formId,
                $this->forms,
                $this->forms[$formId]['hf_id'] ?? '',
                $this->forms[$formId]['total_inspect'] ?? '',
                $this->forms[$formId]['finishingProcedure'] ?? ''
            );

            $this->modalOpen[$formId] = false;

            $this->dispatch(
                'FetchHfNo',
                hf_id: $this->forms[$formId]['hf_id'],
                total_inspect: (int) $this->forms[$formId]['total_inspect'],
                form_id: $formId
            );

            $this->CalcGoodQty($formId);
            $this->receiveDropdownData($this->forms);

            $this->hf_id = '';
            $this->total_inspect = '';
            $this->finishingProcedure = '';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    public function exitHF($formId)
    {
        if (!empty($this->forms[$formId]['hf_id'])) {
            $this->modalOpen[$formId] = false;
            return;
        }

        $this->modalOpen[$formId] = false;

        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);

        $this->hf_id = '';
        $this->total_inspect = '';
    }

    #[On('edit-ppf')]
    public function editPPFFromChild(int $ppf, string $inspectorId)
    {
        Log::info('in Edit PPF:',[
            'ppf' => $ppf,
            'inspectorId' => $this->inspectorId
        ]);
        $this->defectNg = [];

        $data = app(DropdownService::class)->editForms($ppf, $this->inspectorId);
        if (empty($data['forms'])) return;

        $this->toggles = true;
        $this->forms = $data['forms'];
        $this->defectNg = $data['defectNg'];
        $this->reworkNg = $data['reworkNg'];

        $hasAuto = false;

        foreach ($this->forms as $id => $form) {
            $this->CheckHf($id);
            $this->CalcGoodQty($id);
            $this->modalOpen[$id] = false;

            if (in_array($this->forms[$id]['method'] ?? '', self::AUTO_METHODS)) {
                $hasAuto = true;
            }
        }

        $this->activeTab = $hasAuto ? 'auto' : 'worker';

        $this->syncDraft($this->forms);
        Log::info('Forms in Edit PPF:',[
            'forms' => $this->forms
        ]);
    }

    public function editHF($formId)
    {
        $this->modalMode[$formId] = 'edit';
        $this->modalOpen[$formId] = true;
    }

    public function CloseModal($formId)
    {
        $this->modalOpen[$formId] = false;
    }

    public function CheckHf($formId)
    {
        $result = app(DropdownService::class)->checkHf($formId, $this->forms);
        $this->forms = $result['forms'];

        if ($result['error']) {
            $this->addError('forms.' . $formId . '.hf_id', $result['error']);
            $this->hasErrorForm[$formId] = true;
        } else {
            $this->resetErrorBag('forms.' . $formId . '.hf_id');
            $this->hasErrorForm[$formId] = false;
        }

        $this->hasError = $this->hasErrorForm;
        $this->dispatch('hasErrorPren', [
            'message'      => $result['error'],
            'hasError'     => $this->hasError,
            'hasErrorForm' => $this->hasErrorForm,
        ]);
    }

    #[On('operator.defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $this->isSaved = false;

        $this->forms = app(DropdownService::class)->syncFormData(
            $this->forms,
            $data,
            fn($existing, $incoming, $action, $keyResolver) =>
            app(DropdownService::class)->syncCollection($existing, $incoming, $action, $keyResolver)
        );

        $formId = $data['formId'] ?? null;

        if ($formId) {
            $this->reworkNg[$formId] = collect($this->forms[$formId]['rework'] ?? [])->sum('quan');
            $this->CalcGoodQty($formId);
        }

        $this->receiveDropdownData($this->forms);
    }

    public function receiveDropdownData($data)
    {
        $this->dropdownForms = app(DropdownService::class)
            ->receiveDropdownData($data, $this->dropdownForms);

        $this->syncDraft($this->forms);
        $this->dispatch('dropdown-updated', ['forms' => $this->dropdownForms]);
    }

    public function CalcGoodQty($formId)
    {
        $result = app(DropdownService::class)->calcGoodQty(
            $formId,
            $this->forms,
            $this->defectNg,
            $this->reworkNg
        );

        $this->forms[$formId]['GoodQty'] = $result['GoodQty'];
        $this->forms[$formId]['TotalNg'] = $result['TotalNg'];
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }

    private function trackDeletedForm(string $formId, array $formData): void
    {
        $draft = $this->draft->load($this->draftKey());
        $needToDelete = $draft['needToDeleteForm'] ?? [];

        $needToDelete[$formId] = $formData;

        $this->draft->syncComponent($this->draftKey(), 'needToDeleteForm', $needToDelete);
    }

    public function remove($formId)
    {
        if (!isset($this->forms[$formId])) return;

        $this->dispatch('removeError', $formId);
        $this->dispatch('NeedToDeleteForm', $this->forms[$formId]);

        $this->trackDeletedForm($formId, $this->forms[$formId]);

        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);
        $this->resetErrorBag('forms.' . $formId);
        unset($this->hasErrorForm[$formId]);

        $this->forms = [...$this->forms];

        $this->syncDraft($this->forms);
    }

    #[On('action-changed')]
    public function actionChanged(){
        $this->forms = [];
    }
    
};
?>

<div x-data="{
    showFixed: false,
    activeTab: @entangle('activeTab')
}"
    x-init="
    if (!activeTab) { activeTab = 'worker'; }
    const container = document.getElementById('prencode-scroll-container');
    const target = container ?? window;
    target.addEventListener('scroll', () => {
        showFixed = (container ? container.scrollTop : window.scrollY) > 260;
    });

    $wire.on('scroll-to-form', ({ formId }) => {
        $nextTick(() => {
            setTimeout(() => {
                const el = document.querySelector('[wire\\:key=\worker-form-' + formId + '\]');
                if (!el) return;
                const container = document.getElementById('prencode-scroll-container');
                if (container) {
                    const containerRect = container.getBoundingClientRect();
                    const elRect = el.getBoundingClientRect();
                    container.scrollTo({
                        top: container.scrollTop + elRect.top - containerRect.top - 20,
                        behavior: 'smooth'
                    });
                } else {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 150);
        });
    });
">
    <x-ui.dropdown.tab-header />

    @php $autoMethods =['AUTO_DIM','AUTO_SF','AUTO_PLSF','AUTO_NG','AUTO_DIM_NG','AUTO_SF_NG']; @endphp

    <div class="space-y-4 mt-10">
        @foreach($forms as $formId => $form)
        <x-ui.dropdown.form-card
            :form-id="$formId"
            :form="$form"
            :auto-methods="$autoMethods"
            :has-error-form="$hasErrorForm"
            :systemName="'operator'" />
        @endforeach

        @if(session()->has('success'))
        <div x-data="{ open: true }" x-show="open" class="fixed inset-0 flex items-center justify-center bg-gray-800 bg-opacity-50 z-50" x-cloak>
            <div class="bg-white rounded-lg shadow-lg w-96 p-6 text-center relative">
                <button @click="open = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">✕</button>
                <h2 class="text-lg font-semibold text-green-600 mb-2">Success</h2>
                <p class="text-gray-700 mb-4">{{ session('success') }}</p>
                <button @click="open = false" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">OK</button>
            </div>
        </div>
        @endif
    </div>
</div>