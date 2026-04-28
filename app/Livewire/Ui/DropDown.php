<?php

namespace App\Livewire\Ui;

use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
use App\Models\HF\SmallDefect;
use App\Models\Worker;
use App\Models\WorkerName;
use App\Services\DropdownService;
use App\Services\ForReworkService;
use App\Services\PPFService;
use Illuminate\Testing\Fluent\Concerns\Has;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Traits\HandlesFormItems;
use Carbon\Carbon;
use Illuminate\Support\Str;

use function Laravel\Prompts\search;

class DropDown extends Component
{
    use HandlesFormItems;
    public $forms = [];
    public $defects = [];
    public $currentFormId = null;
    public $toggles = false;
    public $hasError = false;
    public $hasErrorForm = [];
    public $isSaved = false;

    public $hf_id = '', $ppf;
    public $total_inspect = '';
    public $finishingProcedure = '';
    public $modalOpen = [];

    public $default = 'new';
    public $expectedQty = 0;
    public  $GoodQty;
    public $isCheckPPF;
    public $dropdownForms = [];
    public array $reworkNg = [];
    public array $defectNg = [];
    public $modalMode;
    public $needToDeleteForm = [];


    public function ppfService(): PPFService
    {
        return $this->ppfService ?? app(PPFService::class);
    }
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
        ];
        $this->modalOpen[$formId] = true;
    }

    #[On('fetchppf')]
    public function fetchppf($data)
    {
        $this->ppf = $data;
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
                'inspect_REC' => uniqid(),
                'hf_name' => '',
                'total_inspect' => '',
                'open' => false, // start expanded by default
                'defects' => [],
                'smallDefects' => [],
                'rework' => [],
                'ForRework' => true,
            ];
            $this->modalOpen[$formId] = true;
        } else {
            $this->dispatch('errorExisting', 'The rework of this PPF is not done yet.');
            return;
        }
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
        return app(DropdownService::class)->calcGoodQty(
            $formId,
            $this->forms,
            $this->defectNg,
            $this->reworkNg
        );
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
