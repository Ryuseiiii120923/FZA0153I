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

use function Laravel\Prompts\search;

class DropDown extends Component
{
    public $forms = [];
    public $defects = [];
    public $currentFormId = null;
    public $toggles = false;
    public $hasError = false;

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
    }
    public function updatedForms()
    {
        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms,
        ]);
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
        $formId = $data['formId'] ?? null;
        if (!$formId) return;

        $this->forms[$formId]['defects'] = array_merge(
            $this->forms[$formId]['defects'] ?? [],
            $data['defects'] ?? []
        );

        foreach ($data['smallDefects'] ?? [] as $large => $smalls) {
            if (!isset($this->forms[$formId]['smallDefects'][$large])) {
                $this->forms[$formId]['smallDefects'][$large] = [];
            }

            foreach ($smalls as $small) {
                $exists = collect($this->forms[$formId]['smallDefects'][$large])
                    ->contains(fn($s) => strtolower(trim($s['type'] ?? '')) === strtolower(trim($small['type'] ?? '')));
                if (!$exists && !empty($small['type'])) {
                    $this->forms[$formId]['smallDefects'][$large][] = $small;
                }
            }
        }
        if (!empty($data['reworksData'])) {
            $this->forms[$formId]['rework'][] = $data['reworksData'];
        }

        $this->dispatch('dropdown-updated', [
            'forms' => $this->forms
        ]);

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
