<?php

namespace App\Livewire\Glcomponents;

use App\Models\DoneRework\Forms;
use App\Models\HF\HF;
use App\Models\Worker;
use App\Services\PPFService;
use App\Services\WorkerService;
use App\Traits\CalculatesGoodQty;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoneRework extends Component
{
    use CalculatesGoodQty;
    public $locked = false, $hasErrorForm = false, $toggle = false;
    public $hf_id, $total_inspect, $goodQty, $modalMode, $ppf, $workerNames = [];
    public $forms = [];
    public $doneReworks = [];
    public $dropdownForms = [];
    public $modalOpen = [];
    public array $reworkNg = [];
    public array $defectNg = [];
    protected $ppfService, $workerService;

    public function render()
    {
        return view('livewire.glcomponents.done-rework');
    }

    public function ppfService(): PPFService
    {
        return $this->ppfService ?? app(PPFService::class);
    }
    public function workerService(): WorkerService
    {
        return $this->workerService ?? app(WorkerService::class);
    }


    public function addNew()
    {
        $this->toggle = true;
        $formId = (string) Str::uuid();
        $this->forms[$formId] = [
            'hf_id' => '',
            'vi_id' => '',
            'vi_name' => '',
            'inspect_REC' => uniqid(),
            'hf_name' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'GoodQty' => 0,
            'TotalNg' => 0,
        ];
        $this->modalOpen[$formId] = true;
    }

    #[On('ClearForm')]
    public function ClearForm(){
        $this->doneReworks = [];
    }

    public function NGQty($formId)
    {
        if (!isset($this->forms[$formId])) return;

        $form = $this->forms[$formId];

        $defectQty = collect($form['defects'] ?? [])->sum('qty');
        $reworkQty = collect($form['rework'] ?? [])->sum('quan');

        $totalNg = $defectQty + $reworkQty;

        // Store NG per form
        $this->defectNg[$formId] = $defectQty;
        $this->reworkNg[$formId] = $reworkQty;

        $this->forms[$formId]['TotalNg'] =  $totalNg;

        return $this->forms[$formId]['TotalNg'];
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

    #[On('gl.defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $formId = $data['formId'] ?? null;
        if (!$formId) return;

        $action = $data['action'] ?? 'add';

        $this->forms[$formId]['defects'] ??= [];
        $this->forms[$formId]['smallDefects'] ??= [];
        $this->forms[$formId]['rework'] ??= [];

        $this->forms[$formId]['defects'] = $this->syncCollection(
            $this->forms[$formId]['defects'],
            $data['defects'] ?? [],
            $action,
            function ($d) {
                $type = strtolower(trim($d['type'] ?? ''));
                $size = strtolower(trim($d['category'] ?? 'large'));
                return $type ? "{$type}_{$size}" : null;
            }
        );

        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {

            $this->forms[$formId]['smallDefects'][$large] = $this->syncCollection(
                $this->forms[$formId]['smallDefects'][$large] ?? [],
                $smalls,
                $action,
                function ($s) {
                    $type = strtolower(trim($s['type'] ?? ''));
                    return $type ?: null;
                }
            );
        }

        $this->forms[$formId]['rework'] = $this->syncCollection(
            $this->forms[$formId]['rework'] ?? [],
            $data['reworksData'] ?? [],
            $action,
            function ($r) {
                $type = strtolower(trim($r['type'] ?? ''));
                $hfno = (int)($r['hfno'] ?? 0);
                return $type ? "{$type}_{$hfno}" : null;
            }
        );
        $this->receiveDropdownData($this->forms);
    }

    public function receiveDropdownData($data)
    {
        foreach ($data as $formId => $formData) {

            if (!isset($this->dropdownForms[$formId])) {
                $this->dropdownForms[$formId] = [];
            }

            $this->dropdownForms[$formId]['defects'] = $formData['defects'] ?? [];
            $this->dropdownForms[$formId]['smallDefects'] = $formData['smallDefects'] ?? [];
            $this->dropdownForms[$formId]['rework'] = $formData['rework'] ?? [];

            foreach ($formData as $key => $value) {
                if (!in_array($key, ['defects', 'smallDefects', 'rework'])) {
                    $this->dropdownForms[$formId][$key] = $value;
                }
            }
        }
        $this->CalcGoodQty($formId);
        $this->dispatch('dropdown-updated-gl', ['forms' => $this->dropdownForms]);
    }

    #[On('gl.FetchNgReworkDropdown')]
    public function FetchNgRework($data)
    {
        $formId = $data['formId'];
        $this->reworkNg[$formId] = $data['totalReworkNg'];
        $this->CalcGoodQty($formId);
    }

    #[On('gl.FetchNgDefectDropdown')]
    public function FetchNgDefect($data)
    {
        $formId = $data['formId'];
        $this->defectNg[$formId] = (int) $data['defectNg'];
        $this->CalcGoodQty($formId);
    }

    public function checkWorkerField($formId, $idField, $nameField)
    {
        $result = $this->workerService()->validateWorker($formId, $this->forms, $idField);

        if (isset($result['error'])) {
            $this->addError(
                'forms.' . $formId . '.' . $idField,
                $result['error']
            );
            $this->forms[$formId][$nameField] = null;
            $this->hasErrorForm[$formId] = true;
            return;
        }

        // Set the name for display
        $this->forms[$formId][$nameField] = $result['worker_name'];
        $this->resetErrorBag('forms.' . $formId . '.' . $idField);
        $this->hasErrorForm[$formId] = false;
    }

    public function CloseModal($formId)
    {
        $this->modalOpen[$formId] = false;
    }
    public function remove($formId)
    {
        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);
        $this->resetErrorBag('forms.' . $formId);
        unset($this->hasErrorForm[$formId]);

        $this->forms = [...$this->forms];
    }
    public function editHF($formId)
    {
        $this->modalMode[$formId] = "edit";
        $this->modalOpen[$formId] = true;
    }


    public function save($formId)
    {
        $this->validate(
            [
                'forms.' . $formId . '.hf_id' => 'required',
                'forms.' . $formId . '.total_inspect' => 'required|numeric|min:1',
            ],
            [
                'forms.' . $formId . '.hf_id.required' => 'HF ID is required!',
                'forms.' . $formId . '.total_inspect.required' => 'Total Inspect is required!',
                'forms.' . $formId . '.total_inspect.numeric' => 'Total Inspect must be a number!',
                'forms.' . $formId . '.total_inspect.min' => 'Total Inspect must be at least 1!',
            ],
            [
                "forms.$formId.hf_id" => "HF ID",
                "forms.$formId.total_inspect" => "Total Inspect",
            ]
        );

        $this->forms[$formId]['hf_id'] = $this->forms[$formId]['hf_id'] ?? $this->hf_id;
        $this->forms[$formId]['total_inspect'] = $this->forms[$formId]['total_inspect'] ?? $this->total_inspect;

        $this->modalOpen[$formId] = false;

        $this->dispatch(
            'FetchHfNo',
            hf_id: $this->forms[$formId]['hf_id'],
            total_inspect: (int) $this->forms[$formId]['total_inspect'],
            form_id: $formId
        );
        $this->calcGoodQtyForForm($formId);

        $this->hf_id = '';
        $this->total_inspect = '';
    }

    public function saveDoneRework()
    {
        $totalNg = collect($this->forms)->sum(function ($form) {
            $defectQty = collect($form['defects'] ?? [])->sum('qty');
            return $defectQty;
        });

        $totalGood = collect($this->forms)->sum(function ($form) {
            return $form['GoodQty'] ?? 0;
        });

        $this->dispatch('FromDoneRework', $totalNg);

        $this->dispatch('UpdateGoodQty', $totalGood);
        $this->dispatch('close-add-done-rework');
    }


    #[On('FetchDoneRework')]
    public function FetchDoneRework($ppf)
    {
        $this->doneReworks = Forms::with(['worker', 'updatedByWorker', 'updatedByEncoder'])
            ->select('hf_id', 'total_inspect', 'updated_by', 'ppfno', 'GoodQty', 'created_at')
            ->where('ppfno', $ppf)
            ->get();

        foreach ($this->doneReworks as $rework) {
            $this->workerNames[$rework->updated_by] = DB::table('社員')
                ->where('社員CD', $rework->updated_by)
                ->value('名前');
        }
    }
}
