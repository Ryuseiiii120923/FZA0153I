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
    public $hf_name = '';
    public $total_inspect = '';

    public $modalOpen = false;
    public function addNew()
    {
        $this->toggles = true;
        $uniqueId = uniqid();
        $this->forms[$uniqueId] = [
            'hf_id' => '',
            'hf_name' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
        ];
        $this->modalOpen = true; 
    }
    public function updatedForms()
    {
        $this->validate([
            'hf_id' => 'required|digits:4',
            'total_inspect' => 'required|numeric|min:1',
        ]);

        $this->forms[$this->currentFormId] = [
            'hf_id' => $this->hf_id,
            'total_inspect' => $this->total_inspect,
            'defects' => [],
            'smallDefects' => [],
            'rework' => [],
        ];

        // Close modal
        $this->modalOpen = false;

        // Reset modal fields
        $this->hf_id = '';
        $this->total_inspect = '';
    }

    public function saveHF(){
        $this->isSaved = true;
        session()->flash('success', 'All changes have been saved!.');
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

        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms
        ]);
    }

    public function CheckHf($formId)
    {
        if (!$formId || !isset($this->forms[$formId])) {
            return;
        }


        if (empty($this->forms[$formId]['hf_id'])) {
            $this->forms[$formId]['hf_id'] = null;
            $this->resetErrorBag('forms.' . $formId . '.hf_id');
            $this->hasError = true;
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

            if ($type === '') continue;

            if (!isset($normalized[$type])) {
                $normalized[$type] = [
                    'type' => $def['type'],
                    'qty'  => $qty
                ];
            } else {
                $normalized[$type]['qty'] += $qty;
            }
        }

        foreach ($data['defects'] ?? [] as $incoming) {
            $type = strtolower(trim($incoming['type'] ?? ''));
            $qty  = (float)($incoming['qty'] ?? 0);
            if ($type === '') continue;

            switch ($action) {

                case 'delete':
                    unset($normalized[$type]);
                    break;

                case 'update':
                    if (isset($normalized[$type])) {
                        $normalized[$type]['qty'] = $qty;
                    } else {
                        $normalized[$type] = [
                            'type' => $incoming['type'],
                            'qty'  => $qty
                        ];
                    }
                    break;

                case 'add':
                default:
                    if (isset($normalized[$type])) {
                        $normalized[$type]['qty'] += $qty;
                    } else {
                        $normalized[$type] = [
                            'type' => $incoming['type'],
                            'qty'  => $qty
                        ];
                    }
                    break;
            }
        }

        $this->forms[$formId]['defects'] = array_values($normalized);
        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {

            $this->forms[$formId]['smallDefects'][$large] ??= [];
            $normalizedSmall = [];

            foreach ($this->forms[$formId]['smallDefects'][$large] as $small) {
                $type = strtolower(trim($small['type'] ?? ''));
                $qty  = (float)($small['qty'] ?? 0);

                if ($type === '') continue;

                if (!isset($normalizedSmall[$type])) {
                    $normalizedSmall[$type] = [
                        'type' => $small['type'],
                        'qty'  => $qty
                    ];
                } else {
                    $normalizedSmall[$type]['qty'] += $qty;
                }
            }

            foreach ($smalls as $incoming) {
                $type = strtolower(trim($incoming['type'] ?? ''));
                $qty  = (float)($incoming['qty'] ?? 0);
                if ($type === '') continue;

                switch ($action) {

                    case 'delete':
                        unset($normalizedSmall[$type]);
                        break;

                    case 'update':
                        if (isset($normalizedSmall[$type])) {
                            $normalizedSmall[$type]['qty'] = $qty;
                        } else {
                            $normalizedSmall[$type] = [
                                'type' => $incoming['type'],
                                'qty'  => $qty
                            ];
                        }
                        break;

                    case 'add':
                    default:
                        if (isset($normalizedSmall[$type])) {
                            $normalizedSmall[$type]['qty'] += $qty;
                        } else {
                            $normalizedSmall[$type] = [
                                'type' => $incoming['type'],
                                'qty'  => $qty
                            ];
                        }
                        break;
                }
            }

            $this->forms[$formId]['smallDefects'][$large] = array_values($normalizedSmall);
        }


        foreach ($data['reworksData'] ?? [] as $incoming) {
            $type = strtolower(trim($incoming['type'] ?? ''));
            $hfno = strtolower(trim($incoming['hfno'] ?? null));
            if ($type === '') continue;
            

            switch ($action) {

                case 'delete':
                    $this->forms[$formId]['rework'] =
                        array_values(array_filter(
                            $this->forms[$formId]['rework'],
                            fn($r) =>
                            strtolower(trim($r['type'] ?? '')) !== $type &&
                                ($r['hfno'] ?? null) !== $hfno
                        ));
                    break;

                case 'update':
                    foreach ($this->forms[$formId]['rework'] as $i => $r) {
                        if (strtolower(trim($r['type'] ?? '')) === $type && strtolower(trim($r['hfno'] ?? '')) === $hfno) {
                            if (isset($incoming['quan'])) {
                                $this->forms[$formId]['rework'][$i]['quan'] = $incoming['quan'];
                            }
                            if (isset($incoming['totalinsp'])) {
                                $this->forms[$formId]['rework'][$i]['totalinsp'] = $incoming['totalinsp'];
                            }
                        }
                    }
                    break;

                case 'add':
                default:
                    $exists = collect($this->forms[$formId]['rework'])
                        ->contains(fn($r) => strtolower(trim($r['type'] ?? '')) === $type && strtolower(trim($r['hfno'] ?? '')) === $hfno);

                    if (!$exists) {
                        $this->forms[$formId]['rework'][] = $incoming;
                    }
                    break;
            }
        }
        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms,
            'action' => $action
        ]);

        dd($this->isSaved);
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }

    public function remove($id)
    {
        unset($this->forms[$id]);
        $this->forms = array_values($this->forms);
    }
    public function render()
    {
        return view('livewire.ui.drop-down');
    }
}
