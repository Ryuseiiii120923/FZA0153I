<?php

namespace App\Livewire\Templates;

use App\Models\Rework as ModelsRework;
use App\Models\Worker;
use App\Models\WorkerName;
use Livewire\Component;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class Rework extends Component
{
    public Collection $reworkcheck;
    public $reworkss = [];
    public $newRework = '';
    //public $hfno = '';
    public $newQuan = '';
    //public $totalInsp = '';
    public array $totalngrework = [];
    public $hfworker = [];
    public $hfname;
    public $editingType;
    public $locked = false;
    public $formId;
    public $editinghfno;

    public array $hfno = [];
    public array $totalInsp = [];
    public $dispatchPrefix;


    public $listeners = [
        'FetchRework' => 'Fetch',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm'
    ];
    public $rules = [
        'newRework' => 'required|string|max:255',
        'newQuan' => 'required|numeric|min:1',
    ];

    protected $messages = [
        'newRework.required' => 'Please enter a rework type.',
        'newQuan.required' => 'Please enter a quantity.',
    ];


    public function mount($dispatchPrefix = null,$formId = null, $loadedRework = [])
    {
        $this->formId = $formId;
        $this->dispatchPrefix = $dispatchPrefix;
        $this->setReworks($loadedRework);
    }


    #[On('FetchHfNo')]
    public function fetchhfno($hf_id, $total_inspect, $form_id)
    {
        $this->hfno[$form_id] = $hf_id;
        $this->totalInsp[$form_id] = (int) $total_inspect;
    }
    // public function fetchhfno($data)
    // {
    //     // Track the HF number and total inspection per form
    //     $this->formId = $data['form_id'] ?? null;

    //     $this->hfno[$this->formId] = $data['hf_id'];
    //     (int) $this->totalInsp[$this->formId] = (int) $data['total_inspect'];
    // }

    //     #[On('FetchHfNo')]
    // public function fetchhfno($data)
    // {
    //     $uniqueId = $data['uniqueId'];

    //     $this->hfno[$uniqueId] = $data['hf_id'];
    //     $this->totalInsp[$uniqueId] = $data['total_inspect'];
    // }



    public function setReworks($loadedReworks)
    {
        $this->reworkss = $loadedReworks;

        // Initialize total NG per form
        $this->totalngrework = [];

        foreach ($loadedReworks as $rework) {
            $formId = $rework['formId'] ?? $this->formId ?? 'default';

            if (!isset($this->totalngrework[$formId])) {
                $this->totalngrework[$formId] = 0;
            }

            $this->totalngrework[$formId] += (int) ($rework['quan'] ?? 0);
        }
    }
    public function locked($data)
    {
        $this->locked = $data;
    }
    public function render()
    {
        $rework = ModelsRework::all();
        return view('livewire.templates.rework', [
            'rework' => $rework,
        ]);
    }
    public function ClearForm($formId = null)
    {
        if ($formId) {
            // Clear only the specified form
            $this->reworkss = collect($this->reworkss)
                ->reject(fn($r) => ($r['formId'] ?? null) === $formId)
                ->values()
                ->toArray();

            $this->totalngrework[$formId] = 0;
        } else {
            // Clear all forms
            $this->reworkss = [];
            $this->totalngrework = [];
        }
    }

    public function Fetch($data)
    {
        $formId = $data['formId'] ?? $this->formId ?? 'default';

        // Filter reworks for this form
        $formReworks = collect($data['reworks'] ?? [])
            ->filter(fn($r) => ($r['formId'] ?? $formId) === $formId)
            ->values()
            ->toArray();

        $this->reworkss = $formReworks;

        // Calculate total NG for this form
        $this->totalngrework[$formId] = collect($formReworks)
            ->sum(fn($x) => (int) ($x['quan'] ?? 0));

        $this->totalngrework[$formId] = $this->totalngrework[$formId] ?? 0;
        $this->dispatch($this->dispatchPrefix . '.FetchNgDropdown', [
            'formId' => $formId,
            'totalReworkNg' => $this->totalngrework[$formId]
        ]);
    }

    public function addRework()
    {
        $this->validate();

        $normalizedNewDefect = strtolower(trim($this->newRework));

        // Get master defects
        $this->reworkcheck = ModelsRework::query()
            ->select('DefectType')
            ->distinct()
            ->whereNotNull('DefectType')
            ->orderBy('DefectType', 'ASC')
            ->get();

        $existsInMaster = $this->reworkcheck
            ->pluck('DefectType')
            ->map(fn($d) => strtolower(trim($d)))
            ->contains($normalizedNewDefect);

        if (!$existsInMaster) {
            $this->addError('newRework', 'This rework defect does not exist in the master list');
            return;
        }

        // Check if rework already exists
        $existing = collect($this->reworkss)->contains(function ($reworkss) use ($normalizedNewDefect) {
            return $reworkss['hfno'] === $this->hfno
                && strtolower(trim($reworkss['type'])) === $normalizedNewDefect;
        });

        $uniquehf = collect($this->reworkss)
            ->pluck('hfno')
            ->unique()
            ->count();

        $isNewHf = !collect($this->reworkss)->pluck('hfno')->contains($this->hfno);
        if ($isNewHf && $uniquehf >= 5) {
            $this->addError('hfno', 'You can only add up to 5 HF Number');
            return;
        }

        if ($existing) {
            $this->addError('newRework', 'This rework is already existing');
            return;
        }

        $newRework = [
            'hfno'      => $this->hfno[$this->formId] ?? '',
            'totalinsp' => $this->totalInsp[$this->formId] ?? '',
            'type'      => trim($this->newRework),
            'quan'      => $this->newQuan,
        ];

        $this->reworkss[] = $newRework;



        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => $this->reworkss,
            'formId'      => $this->formId,
            'action'      => 'add'
        ]);

        $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);

        $this->UpdatedNgRework($this->formId);
        // Clear input fields
        $this->newRework = '';
        $this->newQuan   = '';
    }

    public function CheckHf()
    {
        if (empty($this->hfno)) {
            $this->hfname[$this->formId] = null;
            $this->resetErrorBag('hfno');
            return;
        }

        $searchValue = (strlen($this->hfno[$this->formId]) === 2) ? ' ' . $this->hfno : $this->hfno;
        $hf = Worker::where('作業員CD', $searchValue)->first();

        if ($hf) {
            $name = WorkerName::where('社員CD', $hf->社員CD)->first();
            $this->hfname = $name ? $name->名前 : null;
            $this->resetErrorBag('hfno');
        } else {
            $this->addError('hfno', 'This Operator does not exist');
            $this->hfno[$this->formId] = "";
            $this->hfname = null;
        }
    }


    public function deleteRework($hfno, $type)
    {
        $hfno = trim($hfno);
        $type = trim(strtoupper($type));

        $this->reworkss = collect($this->reworkss)
            ->reject(
                fn($rework) =>
                trim($rework['hfno'] ?? $rework['newhfno'] ?? '') === $hfno &&
                    trim(strtoupper($rework['type'] ?? $rework['newtype'] ?? '')) === $type
            )
            ->values()
            ->toArray();

        $this->UpdatedNgRework($this->formId);

        // Send to the other component
        // $this->dispatch('FromReworks', reworksData: $reworksData);

        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => [[
                'hfno' => $hfno,
                'type' => $type,
            ]],
            'formId' => $this->formId,
            'action' => 'delete'
        ]);
        $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);
    }


    public function UpdatedNgRework($formId = null)
    {
        $formId = $formId ?? $this->formId;
        if (!$formId) return;

        // Sum only the reworks belonging to    this form
        $this->totalngrework[$formId] = collect($this->reworkss)
            ->filter(fn($r) => $r['hfno'] === ($this->hfno[$formId] ?? ''))
            ->sum(fn($x) => (int) $x['quan'] ?? 0);

        $this->dispatch($this->dispatchPrefix . '.FetchNgReworkDropdown', [
            'formId' => $formId,
            'totalReworkNg' => $this->totalngrework[$formId],
        ]);
    }

    public function updateRework()
    {
        foreach ($this->reworkss as &$rework) {
            if ($rework['type'] === $this->editingType && $rework['hfno'] === $this->editinghfno) {
                $rework['quan'] = $this->newQuan;
                $rework['hfno'] = $this->hfno[$this->formId];
                $rework['totalinsp'] = $this->totalInsp[$this->formId];
                break;
            }
        }


        $this->dispatch($this->dispatchPrefix . '.defects-updated', [
            'reworksData' => [[
                'hfno' => $this->hfno[$this->formId] ?? null,
                'type' => $this->editingType,
                'quan' => $this->newQuan,
                'totalinsp' => $this->totalInsp[$this->formId] ?? null,
            ]],
            'formId' => $this->formId,
            'action' => 'update'
        ]);


        $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);
        $this->UpdatedNGRework();
        $this->editingType = null;
        $this->newQuan = '';
        $this->totalInsp[$this->formId] = '';
    }


    public function startEditRework($formId, $type, $hfno)
    {
        $this->editingType = $type;
        $this->editinghfno = $hfno;
        $this->formId = $formId;

        $rework = collect($this->reworkss)
            ->first(
                fn($r) => ($r['type'] ?? '') === $type &&
                    ($r['hfno'] ?? '') == $hfno
            );

        if ($rework) {
            $this->hfno[$formId] = $rework['hfno'];
            $this->newQuan = $rework['quan'];
            $this->totalInsp[$formId] = $rework['totalinsp'];
        }
    }
}
