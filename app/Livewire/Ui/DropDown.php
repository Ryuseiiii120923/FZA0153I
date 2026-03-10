<?php

namespace App\Livewire\Ui;

use App\Models\HF\Defect;
use App\Models\HF\HF;
use App\Models\HF\Rework;
use App\Models\HF\SmallDefect;
use App\Models\Worker;
use App\Models\WorkerName;
use Illuminate\Testing\Fluent\Concerns\Has;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Traits\HandlesFormItems;
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
    public $isSaved = false;

    public $hf_id = '';
    public $total_inspect = '';
    public $modalOpen = [];

    public $default = 'new';
    public $expectedQty = 0;
    public $dropdownForms = [];

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
            'hf_name' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
        ];
        $this->modalOpen[$formId] = true;
    }
    public function updatedForms()
    {
        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms,
        ]);
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
                'forms.' . $formId . '.hf_id' => 'required|digits:4',
                'forms.' . $formId . '.total_inspect' => 'required|numeric|min:1',
            ],
            [
                'forms.' . $formId . '.hf_id.required' => 'HF ID is required!',
                'forms.' . $formId . '.hf_id.digits' => 'HF ID must be 4 digits!',
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

        // Reset modal fields
        $this->hf_id = '';
        $this->total_inspect = '';
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

        $hfRecords = HF::where('ppfno', $ppf)
            ->where('updated_by', $inspectorId)
            ->get();

        if ($hfRecords->isEmpty()) return;

        $this->toggles = true;

        foreach ($hfRecords as $h) {

            $operatorDefects = Defect::where('ppfno', $ppf)
                ->where('updated_by', $inspectorId)
                ->where('hf_id', $h->hf_id) // <-- use correct column
                ->get()
                ->map(function ($d) {
                    return [
                        'type' => $d->defect,
                        'qty' => $d->qty ?? 1,
                    ];
                })
                ->toArray();

            $operatorRework = Rework::where('ppfno', $ppf)
                ->where('updated_by', $inspectorId)
                ->where('hf_id', $h->hf_id)
                ->get()
                ->map(function ($r) {
                    return [
                        'hfno' => $r->hfno,
                        'totalinsp' => $r->totalinsp ?? 0,
                        'type' => $r->rework_type,
                        'quan' => $r->qty ?? 1,
                    ];
                })
                ->toArray();


            $operatorSmallDefects = SmallDefect::where('ppfno', $ppf)
                ->where('updated_by', $inspectorId)
                ->where('hf_id', $h->hf_id)
                ->whereNotNull('large_defect')
                ->get()
                ->groupBy('large_defect')
                ->map(fn($group) => $group->values()->toArray())
                ->toArray();

            $uniqueId = uniqid();
            $this->forms[$uniqueId] = [
                'hf_id' => $h->hf_id,
                'ppfno' => $h->ppfno,
                'total_inspect' => $h->total_inspect,
                'open' => true,
                'defects' => $operatorDefects,
                'smallDefects' => $operatorSmallDefects,
                'rework' => $operatorRework,
            ];
            $this->CheckHf($uniqueId);
        }

        if ($this->hasError) {
            $this->dispatch('hasErrorPren', $this->hasError);
        } else {
            $this->dispatch('dropdown-updated', [
                'forms' => $this->forms
            ]);
        }
    }



    public function CheckHf($formId)
    {
        $currentHfId = $this->forms[$formId]['hf_id'];
        if (!$formId || !isset($this->forms[$formId])) {
            return;
        }
        foreach ($this->forms as $id => $form) {
            if ($id === $formId) continue; // skip current form
            if (!empty($form['hf_id']) && $form['hf_id'] === $currentHfId) {
                $this->addError(
                    'forms.' . $formId . '.hf_id',
                    'This Operator is already used in another form'
                );
                $this->hasError = true;
                $this->dispatch('hasErrorPren', $this->hasError);
                return;
            }
        }


        if (empty($this->forms[$formId]['hf_id'])) {
            $this->forms[$formId]['hf_id'] = null;
            $this->resetErrorBag('forms.' . $formId . '.hf_id');
            $this->addError(
                'forms.' . $formId . '.hf_id',
                'This Operator already exist'
            );
            $this->hasError = true;
            $this->dispatch('hasErrorPren', $this->hasError);
            return;
        }

        $searchValue = strlen($this->forms[$formId]['hf_id']) === 2
            ? ' ' . $this->forms[$formId]['hf_id']
            : $this->forms[$formId]['hf_id'];

        $hf = Worker::where('作業員CD', $searchValue)
            ->where('区分', 1)
            ->first();

        if ($hf) {
            $name = WorkerName::where('社員CD', $hf->社員CD)->first();

            $this->forms[$formId]['hf_name'] = $name?->名前;
            $this->resetErrorBag('forms.' . $formId . '.hf_id');
            $this->hasError = false;
        } else {
            $this->addError(
                'forms.' . $formId . '.hf_id',
                'This Operator does not exist'
            );

            $this->forms[$formId]['hf_id'] = null;
            $this->forms[$formId]['hf_name'] = null;
            $this->hasError = true;
        }
        $this->dispatch('hasErrorPren', $this->hasError);
    }

    #[On('defects-updated')]
    public function updateDefectsFromChild($data = [])
    {
        $this->isSaved = false; // Mark as unsaved when defects are updated
        $formId = $data['formId'] ?? null;
        if (!$formId) return;

        $action = $data['action'] ?? 'add';

        // Ensure structure exists
        $this->forms[$formId]['defects'] ??= [];
        $this->forms[$formId]['smallDefects'] ??= [];
        $this->forms[$formId]['rework'] ??= [];

        $normalized = [];


        // Start from existing defects
        foreach ($this->forms[$formId]['defects'] as $def) {

            $type = strtolower(trim($def['type'] ?? ''));
            $qty  = (float)($def['qty'] ?? 0);
            $size  = strtolower(trim($def['category'] ?? 'large'));

            if ($type === '') continue;

            $key = $type . '_' . $size;
            if (!isset($normalized[$key])) {
                $normalized[$key] = [
                    'type' => $def['type'],
                    'category' => $size,
                    'qty'  => $qty
                ];
            } else {
                $normalized[$key]['qty'] += $qty;
            }
        }

        $map = collect($this->forms[$formId]['defects'])
            ->keyBy(fn($d) => strtolower(trim($d['type'])) . '_' . strtolower(trim($d['category'] ?? 'large')))
            ->toArray();

        foreach ($data['defects'] ?? [] as $incoming) {

            $type = strtolower(trim($incoming['type'] ?? ''));
            $size = strtolower(trim($incoming['category'] ?? 'large'));
            $qty  = (float)($incoming['qty'] ?? 0);

            if ($type === '') continue;

            $key = $type . '_' . $size;

            switch ($action) {

                case 'delete':
                    unset($map[$key]);
                    break;

                case 'update':
                case 'add':
                    $map[$key] = [
                        'type' => $incoming['type'],
                        'category' => $size,
                        'qty' => $qty
                    ];
                    break;
            }
        }

        $this->forms[$formId]['defects'] = array_values($map);

        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {

            // Create map from existing small defects
            $map = collect($this->forms[$formId]['smallDefects'][$large] ?? [])
                ->keyBy(fn($s) => strtolower(trim($s['type'] ?? '')))
                ->toArray();

            foreach ($smalls as $incoming) {

                $type = strtolower(trim($incoming['type'] ?? ''));
                $qty  = (float)($incoming['qty'] ?? 0);

                if ($type === '') continue;

                switch ($action) {

                    case 'delete':
                        unset($map[$type]);
                        break;

                    case 'update':
                    case 'add':
                        $map[$type] = [
                            'type' => $incoming['type'],
                            'qty'  => $qty
                        ];
                        break;
                }
            }

            $this->forms[$formId]['smallDefects'][$large] = array_values($map);
        }

        $map = collect($this->forms[$formId]['rework'] ?? [])
            ->keyBy(fn($r) => strtolower(trim($r['type'])) . '_' . (int)$r['hfno'])
            ->toArray();

        foreach ($data['reworksData'] ?? [] as $incoming) {

            $type = strtolower(trim($incoming['type'] ?? ''));
            $hfno = (int)($incoming['hfno'] ?? 0);

            if ($type === '') continue;

            $key = $type . '_' . $hfno;

            switch ($action) {

                case 'delete':
                    unset($map[$key]);
                    break;

                case 'update':
                case 'add':
                    $map[$key] = $incoming;
                    break;
            }
        }

        $this->forms[$formId]['rework'] = array_values($map);

        // foreach ($data['reworksData'] ?? [] as $incoming) {
        //     $type = strtolower(trim($incoming['type'] ?? ''));
        //     $hfno = (int)trim($incoming['hfno'] ?? null);
        //     if ($type === '') continue;


        //     switch ($action) {

        //         case 'delete':
        //             $this->forms[$formId]['rework'] =
        //                 array_values(array_filter(
        //                     $this->forms[$formId]['rework'],
        //                     fn($r) =>
        //                     strtolower(trim($r['type'] ?? '')) !== $type &&
        //                         ($r['hfno'] ?? null) !== $hfno
        //                 ));
        //             break;

        //         case 'update':
        //             foreach ($this->forms[$formId]['rework'] as $i => $r) {
        //                 if (strtolower(trim($r['type'] ?? '')) === $type && (int)trim($r['hfno'] ?? '') === $hfno) {
        //                     if (isset($incoming['quan'])) {
        //                         $this->forms[$formId]['rework'][$i]['quan'] = $incoming['quan'];
        //                     }
        //                     if (isset($incoming['totalinsp'])) {
        //                         $this->forms[$formId]['rework'][$i]['totalinsp'] = $incoming['totalinsp'];
        //                     }
        //                 }
        //             }
        //             break;

        //         case 'add':
        //         default:
        //             $exists = collect($this->forms[$formId]['rework'])
        //                 ->contains(fn($r) => strtolower(trim($r['type'] ?? '')) === $type && strtolower(trim($r['hfno'] ?? '')) === $hfno);

        //             if (!$exists) {
        //                 $this->forms[$formId]['rework'][] = $incoming;
        //             }
        //             break;
        //     }
        // }



        // $this->dispatch('dropdown-updated', [
        //     'forms' => $this->forms
        // ]);

        $this->receiveDropdownData($this->forms);
    }

    public function receiveDropdownData($data)
    {
        foreach ($data as $formId => $formData) {

            if (!isset($this->dropdownForms[$formId])) {
                $this->dropdownForms[$formId] = [];
            }

            // Just replace with the normalized data
            $this->dropdownForms[$formId]['defects'] = $formData['defects'] ?? [];
            $this->dropdownForms[$formId]['smallDefects'] = $formData['smallDefects'] ?? [];
            $this->dropdownForms[$formId]['rework'] = $formData['rework'] ?? [];

            // keep other fields
            foreach ($formData as $key => $value) {
                if (!in_array($key, ['defects', 'smallDefects', 'rework'])) {
                    $this->dropdownForms[$formId][$key] = $value;
                }
            }
        }

        $this->dispatch('dropdown-updated', ['forms' => $this->dropdownForms]);
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }

    public function remove($formId)
    {
        unset($this->forms[$formId]);
        unset($this->modalOpen[$formId]);

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
