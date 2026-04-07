<?php

namespace App\Livewire\Ui;

use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
use App\Models\HF\SmallDefect;
use App\Models\Worker;
use App\Models\WorkerName;
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
    public $modalOpen = [];

    public $default = 'new';
    public $expectedQty = 0;
    public $dropdownForms = [];
    public  $GoodQty;
    public $isCheckPPF;

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
            'hf_name' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
            'ForRework' => false,
            'TotalNg' => [],
            'GoodQty' => [],
            'TotalRework' => []
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

        // Save values
        $this->forms[$formId]['hf_id'] = $this->forms[$formId]['hf_id'] ?? $this->hf_id;
        $this->forms[$formId]['total_inspect'] = $this->forms[$formId]['total_inspect'] ?? $this->total_inspect;

        // Close modal
        $this->modalOpen[$formId] = false;

        // $this->dispatch('FetchHfNo', [
        //     'hf_id' =>  $this->forms[$formId]['hf_id'],
        //     'total_inspect' =>   (int) $this->forms[$formId]['total_inspect'],
        //     'form_id' => $formId,
        // ]);
        $this->dispatch(
            'FetchHfNo',
            hf_id: $this->forms[$formId]['hf_id'],
            total_inspect: (int) $this->forms[$formId]['total_inspect'],
            form_id: $formId
        );

        $this->CalcGoodQty($formId);
        $this->receiveDropdownData($this->forms);
        // Reset modal fields
        $this->hf_id = '';
        $this->total_inspect = '';
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
        $hfRecords = HF::where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->get();

        if ($hfRecords->isEmpty()) return;

        $this->toggles = true;

        foreach ($hfRecords as $h) {

            $operatorDefects = Defect::where('ppfno', $ppf)
                ->where('updated_by', $inspectorId)
                ->where('inspect_REC', $h->inspect_REC)
                ->get()
                ->groupBy(fn($d) => strtolower(trim($d->defect)))
                ->map(function ($group) {
                    $first = $group->first();
                    $totalQty = $group->sum(fn($d) => $d->qty ?? 1);
                    return [
                        'id' => $first->RECNO,
                        'type' => $first->defect,
                        'qty'  => $totalQty,
                    ];
                })
                ->values()
                ->toArray();
            $this->defectNg[$h->hf_id] = collect($operatorDefects)->sum('qty');

            $operatorRework = Rework::where('ppfno', $ppf)
                ->where('updated_by', $inspectorId)
                ->where('inspect_REC', $h->inspect_REC)
                ->get()
                ->map(function ($r) {
                    return [
                        'id' => $r->RECNO,
                        'hfno' => $r->hfno,
                        'totalinsp' => $r->totalinsp ?? 0,
                        'type' => $r->rework_type,
                        'quan' => $r->qty ?? 1,
                    ];
                })
                ->toArray();
            $this->reworkNg[$h->hf_id] = collect($operatorRework)->sum('quan');
            $operatorSmallDefects = SmallDefect::where('ppfno', $ppf)
                ->where('updated_by', $inspectorId)
                ->where('inspect_REC', $h->inspect_REC)
                ->whereNotNull('large_defect')
                ->get()
                ->groupBy('large_defect')
                ->mapWithKeys(function ($group, $largeDefect) {
                    return [
                        $largeDefect => collect($group)->map(fn($s) => [
                            'id' => $s->RECNO,
                            'type' => $s->small_defect,
                            'qty'  => $s->qty ?? 0,
                        ])->toArray()
                    ];
                })
                ->toArray();
            // dd($operatorSmallDefects);
            $uniqueId = uniqid();
            $selectedLarge = array_key_first($operatorSmallDefects) ?? null;
            $this->forms[$uniqueId] = [
                'id' => $h->RECNO,
                'inspect_REC' => $h->inspect_REC,
                'hf_id' => $h->hf_id,
                'ppfno' => $h->ppfno,
                'total_inspect' => $h->total_inspect,
                'open' => true,
                'defects' => $operatorDefects,
                'created_at' => $h->created_at ?? null,
                'updated_date' => $h->updated_date ?? null,
                'smallDefects' => $operatorSmallDefects,
                'selectedLargeDefect' => $selectedLarge,
                'rework' => $operatorRework,
                'isRework' => (bool) $h->IsDoneRework,
                'ForRework' => (bool) $h->ForRework
            ];
            $this->CheckHf($uniqueId);
            $this->CalcGoodQty($uniqueId);
            $this->modalOpen[$uniqueId] = false;
        }

        if ($this->hasError) {
            $this->dispatch('hasErrorPren', $this->hasError);
        } else {
            $this->dispatch('dropdown-updated', [
                'forms' => $this->forms
            ]);
        }
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
        if (!$formId || !isset($this->forms[$formId])) {
            return;
        }

        $currentHfId = $this->forms[$formId]['hf_id'];
        $currentDate = now()->format('Y-m-d');

        if (empty($currentHfId)) {

            $this->forms[$formId]['hf_name'] = null;

            $this->addError(
                'forms.' . $formId . '.hf_id',
                'HF ID cannot be empty'
            );

            $this->hasErrorForm[$formId] = true;
            $this->hasError = $this->hasErrorForm;

            $this->dispatch('hasErrorPren', [
                'hasError' => $this->hasError,
                'hasErrorForm' => $this->hasErrorForm
            ]);

            return;
        }

        $searchValue = strlen($currentHfId) === 2
            ? ' ' . $currentHfId
            : $currentHfId;

        $hf = Worker::where('作業員CD', $searchValue)
            ->where('区分', 1)
            ->first();

        if (!$hf) {

            $this->addError(
                'forms.' . $formId . '.hf_id',
                'This Operator does not exist'
            );

            $this->forms[$formId]['hf_name'] = null;
            $this->hasErrorForm[$formId] = true;
            $this->hasError = $this->hasErrorForm;

            $this->dispatch('hasErrorPren', [
                'hasError' => $this->hasError,
                'hasErrorForm' => $this->hasErrorForm
            ]);

            return;
        }

        // 4️⃣ Get worker name
        $name = WorkerName::where('社員CD', $hf->社員CD)->first();

        $this->forms[$formId]['hf_name'] = $name?->名前;

        $this->resetErrorBag('forms.' . $formId . '.hf_id');
        $this->hasErrorForm[$formId] = false;

   
        foreach ($this->forms as $id => $form) {

            if ($id === $formId) continue;

            $otherDate = isset($form['created_at'])
                ? Carbon::parse($form['created_at'])->format('Y-m-d')
                : null;

            if (
                isset($form['hf_id']) &&
                $form['hf_id'] === $currentHfId &&
                $currentDate === $otherDate
            ) {

                $this->addError(
                    'forms.' . $formId . '.hf_id',
                    'This Operator is already used in another form with the same date'
                );

                $this->hasErrorForm[$formId] = true;
                $this->hasError = $this->hasErrorForm;

                $this->dispatch('hasErrorPren', [
                    'hasError' => $this->hasError,
                    'hasErrorForm' => $this->hasErrorForm
                ]);

                return;
            }
        }

        // 6️⃣ Final: update global error array after all checks
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

        $this->reworkNg[$formId] = collect($this->forms[$formId]['rework'])->sum('quan');
        $this->CalcGoodQty($formId);

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
        $this->dispatch('dropdown-updated', ['forms' => $this->dropdownForms]);
    }

    // #[On('operator.FetchNgReworkDropdown')]
    // public function FetchNgRework($data)
    // {
    //     $formId = $data['formId'];
    //     $this->reworkNg[$formId] = $data['totalReworkNg'];
    //     $this->CalcGoodQty($formId);
    // }

    // #[On('operator.FetchNgDefectDropdown')]
    // public function FetchNgDefect($data)
    // {
    //     $formId = $data['formId'];
    //     $this->defectNg[$formId] = (int) $data['defectNg'];
    //     $this->CalcGoodQty($formId);
    // }
    public function CalcGoodQty($formId)
    {
        if (!isset($this->forms[$formId])) return;

        $form = $this->forms[$formId];

        $defectQty = isset($this->defectNg[$formId])
            ? $this->defectNg[$formId]
            : collect($form['defects'] ?? [])->sum('qty');

        $reworkQty = isset($this->reworkNg[$formId])
            ? $this->reworkNg[$formId]
            : collect($form['rework'] ?? [])->sum('quan');

        $totalNg = $defectQty ?? 0 + $reworkQty ?? 0;

        $this->forms[$formId]['GoodQty'] = ($form['total_inspect'] ?? 0) - $totalNg - $reworkQty;
        $this->forms[$formId]['TotalNg'] = $totalNg;
        $this->forms[$formId]['TotalRework'] = $reworkQty;

        return [
            $this->forms[$formId]['GoodQty'],
            $this->forms[$formId]['TotalNg']
        ];
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
