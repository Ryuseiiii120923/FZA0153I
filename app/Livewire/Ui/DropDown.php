<?php

namespace App\Livewire\Ui;
use App\Services\DropdownService;
use App\Services\ForReworkService;
use App\Services\PPFService;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Traits\HandlesFormItems;
use Illuminate\Support\Str;

class DropDown extends Component
{
    use HandlesFormItems;
    public $forms = [];
    public $toggles = false;
    public $hasError = false;
    public $hasErrorForm = [];
    public $isSaved = false;

    public $hf_id = '', $ppf;
    public $total_inspect = '';
    public $finishingProcedure = '';
    public $modalOpen = [];

    public $expectedQty = 0;
    public $isCheckPPF;
    public $dropdownForms = [];
    public array $reworkNg = [];
    public array $defectNg = [];
    public $modalMode;

    public function forReworkService(): ForReworkService
    {
        return app(ForReworkService::class);
    }

    public function mount()
    {
        foreach ($this->forms as $formId => $form) {
            $this->modalOpen[$formId] = false; // default all modals closed
        }
    }

    public function addNew()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'VI',
            'Process' => '100% VI'
        ];
        $this->modalOpen[$formId] = true;
    }

    public function addNewHF()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => $this->hf_id,
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'HF',
            'Process' => 'HF'
        ];
        $this->modalOpen[$formId] = true;
    }

    public function saveRemarks($formId)
    {
        $remarks = $this->forms[$formId]['Remarks'] ?? '';

        $this->forms[$formId]['Remarks'] = trim($remarks);

        // refresh dropdown data if needed
        $this->receiveDropdownData($this->forms);
    }

    public function addNewPL()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => null,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'method' => 'PL',
            'Remarks' => [],
        ];
        $this->modalOpen[$formId] = true;
    }
    public function addNewSF()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'ForRework' => null,
            'smallDefects' => [],
            'rework' => [],
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'method' => 'SF',
            'Remarks' => [],
        ];
        $this->modalOpen[$formId] = true;
    }


    public function addNewDoneRework()
    {

        $result = $this->forReworkService()->fetchIfFlgDone($this->ppf);
        $flgDone = $result['FlgDone'] ?? false;
        $ProceedToRework = $result['ProceedToRework'] ?? false;


        if ($flgDone && $ProceedToRework) {
            $this->toggles = true;
            $formId = (string) Str::uuid();
            $this->forms[$formId] = [
                'hf_id' => '',
                'formId' => $formId,
                'inspect_REC' => uniqid(),
                'hf_name' => '',
                'total_inspect' => '',
                'open' => false, // start expanded by default
                'defects' => [],
                'smallDefects' => [],
                'rework' => [],
                'ForRework' => true,
                'Remarks' => '',
                'Operation' => 'VI',
                'Process' => 'SRW'
            ];
            $this->modalOpen[$formId] = true;
        } elseif (!$ProceedToRework) {
            $this->dispatch('errorExisting', 'Please confirm first in For Rework Table in Dashboard.');
            return;
        } elseif (!$flgDone) {
            $this->dispatch('errorExisting', 'Please Encode first in HFRW.');
            return;
        }
    }

    public function addNewAutoDimension()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'AUTO',
            'Process' => 'Auto Dimension Checking',
            'method' => 'AUTO_DIM',
        ];
        $this->modalOpen[$formId] = true;
    }

    public function addNewAutoSF()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'AUTO',
            'Process' => 'Auto SF Inspection',
            'method' => 'AUTO_SF',
        ];
        $this->modalOpen[$formId] = true;
    }

    public function addNewAutoPLSF()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'AUTO',
            'Process' => 'Auto PL/SF Inspection',
            'method' => 'AUTO_PLSF',
        ];
        $this->modalOpen[$formId] = true;
    }

    public function addNewAutoNG()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'AUTO',
            'Process' => 'Auto NG Checking',
            'method' => 'AUTO_NG',
        ];
        $this->modalOpen[$formId] = true;
    }

    public function addNewAutoDimNG()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'AUTO',
            'Process' => 'Auto Dimension of VI Good from NG',
            'method' => 'AUTO_DIM_NG',
        ];
        $this->modalOpen[$formId] = true;
    }

    public function addNewAutoSFNG()
    {
        $this->toggles = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'inspect_REC' => uniqid(),
            'formId' => $formId,
            'hf_name' => '',
            'finishingProcedure' => '',
            'total_inspect' => '',
            'open' => false,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => [],
            'Remarks' => '',
            'Operation' => 'AUTO',
            'Process' => 'Auto SF Dimension of VI Good from NG',
            'method' => 'AUTO_SF_NG',
        ];
        $this->modalOpen[$formId] = true;
    }


    #[On('fetchppf')]
    public function fetchppf($data)
    {
        $this->ppf = $data;
    }

    public function updatedForms()
    {
        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms,
        ]);
    }

    #[On('IsCheckPPF')]
    public function IsCheckPPF($data)
    {
        $this->isCheckPPF = $data;
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
                $this->hf_id,
                $this->total_inspect,
                $this->finishingProcedure
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

            // Reset fields
            $this->hf_id = '';
            $this->total_inspect = '';
            $this->finishingProcedure = '';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    #[On('ClearFormDropdown')]
    public function ClearForm()
    {
        $this->forms = [];
    }

    public function exitHF($formId)
    {
        // Close modal without saving
        $this->modalOpen[$formId] = false;

        if (isset($this->forms[$formId])) {
            unset($this->forms[$formId]);
        }

        // Remove modal state
        if (isset($this->modalOpen[$formId])) {
            unset($this->modalOpen[$formId]);
        }
        // Reset modal fields
        $this->hf_id = '';
        $this->total_inspect = '';
    }

    #[On('edit-ppf')]
    public function editPPFFromChild($ppf, $inspectorId)
    {
        $this->defectNg = [];

        $data = app(DropdownService::class)->editForms($ppf, $inspectorId);
        if (empty($data['forms'])) return;

        $this->toggles = true;
        $this->forms = $data['forms'];
        $this->defectNg = $data['defectNg'];
        $this->reworkNg = $data['reworkNg'];

        foreach ($this->forms as $id => $form) {
            $this->CheckHf($id);
            $this->CalcGoodQty($id);
            $this->modalOpen[$id] = false;
        }

        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms
        ]);
    }


    public function editHF($formId)
    {
        $this->modalMode[$formId] = "edit";
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
            'message' => $result['error'],
            'hasError' => $this->hasError,
            'hasErrorForm' => $this->hasErrorForm
        ]);
    }

    private function syncCollection(array $existing, array $incoming, string $action, callable $keyBuilder)
    {
        $map = collect($existing)
            ->keyBy($keyBuilder)
            ->toArray();

        foreach ($incoming as $item) {

            $key = $keyBuilder($item);

            if (!$key) continue;

            switch ($action) {
                case 'delete':
                    unset($map[$key]);
                    break;

                case 'update':
                case 'add':
                    $map[$key] = $item;
                    break;
            }
        }

        return array_values($map);
    }


    #[On('operator.defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $this->isSaved = false;

        $this->forms = app(DropdownService::class)->syncFormData(
            $this->forms,
            $data,
            function ($existing, $incoming, $action, $keyResolver) {
                return $this->syncCollection($existing, $incoming, $action, $keyResolver);
            }
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

        $this->dispatch('dropdown-updated', [
            'forms' => $this->dropdownForms
        ]);
    }

    public function CalcGoodQty($formId)
    {
        $result =  app(DropdownService::class)->calcGoodQty(
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

    public function remove($formId)
    {
        if (!isset($this->forms[$formId])) return;
        $this->dispatch('removeError', $formId);
        $this->dispatch('NeedToDeleteForm', $this->forms[$formId]);
        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);
        $this->resetErrorBag('forms.' . $formId);
        unset($this->hasErrorForm[$formId]);

        // Force Livewire refresh
        $this->forms = [...$this->forms];

        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms
        ]);
    }
    public function render()
    {
        return view('livewire.ui.drop-down');
    }
}
