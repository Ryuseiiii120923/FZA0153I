<?php

namespace App\Livewire\Glcomponents;


use App\Models\Rework as ModelsRework;
use App\Models\Worker;
use App\Models\WorkerName;
use Livewire\Component;
use Illuminate\Support\Collection;

class Reworks extends Component
{
    public Collection $reworkcheck;
    public $reworkss = [];
    public $newRework = '';
    public $hfno = '';
    public $newQuan = '';
    public $totalInsp = '';
    public $totalngrework = 0;
    public $hfworker = [];
    public $hfname;
    public $editingType;
    public $locked = false;
    public $formId;

    public $listeners = [
        'FetchRework' => 'Fetch',
        'locked' => 'locked',
        'ClearForm' => 'ClearForm'
    ];
    public $rules = [
        'hfno' => 'required|string|max:50',
        'totalInsp' => 'required|numeric|min:1',
        'newRework' => 'required|string|max:255',
        'newQuan' => 'required|numeric|min:1',
    ];

    protected $messages = [
        'hfno.required' => 'Please enter HF number.',
        'totalInsp.required' => 'Please enter total inspections.',
        'newRework.required' => 'Please enter a rework type.',
        'newQuan.required' => 'Please enter a quantity.',
    ];

    public function locked($data)
    {
        $this->locked = $data;
    }
    public function render()
    {
        $rework = ModelsRework::all();
        return view('livewire.glcomponents.reworks', [
            'rework' => $rework,
        ]);
    }
    public function ClearForm()
    {
        $this->reworkss = [];
        $this->totalngrework = null;
    }

    public function Fetch($data)
    {
        $this->reworkss = $data['reworks'];
        $this->totalngrework = collect($this->reworkss)
            ->sum(fn($x) => (int) $x['quan']);
    }

    public function addRework()
{
    $this->validate();
    $normalizedNewDefect = strtolower(trim($this->newRework));

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

    $existing = collect($this->reworkss)->contains(function ($reworkss) use ($normalizedNewDefect) {
            return $reworkss['hfno'] === $this->hfno and strtolower(trim($reworkss['type'])) === $normalizedNewDefect;
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

    $this->reworkss[] = [
        'hfno'       => $this->hfno,
        'totalinsp'  => $this->totalInsp,
        'type'       => trim($this->newRework),
        'quan'       => $this->newQuan,
    ];

    $reworksData = [
        'newhfno'   => $this->hfno,
        'newtype'   => $this->newRework, 
        'newquan'   => $this->newQuan,
        'totalinsp' => $this->totalInsp,
        'action'    => 'add',
    ];
    $this->UpdatedNGRework();

    $this->dispatch('FromReworks', reworksData: $reworksData);
    $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);
    //dd($reworksData);
    // Clear input fields
    $this->hfno       = '';
    $this->totalInsp  = '';
    $this->newRework  = '';
    $this->newQuan    = '';
}


    public function CheckHf()
{
    if (empty($this->hfno)) {
        $this->hfname = null;
        $this->resetErrorBag('hfno');
        return;
    }

    $searchValue = (strlen($this->hfno) === 2) ? ' ' . $this->hfno : $this->hfno;
    $hf = Worker::where('作業員CD', $searchValue)->first();

    if ($hf) {
        $name = WorkerName::where('社員CD', $hf->社員CD)->first();
        $this->hfname = $name ? $name->名前 : null;
        $this->resetErrorBag('hfno');
    } else {
        $this->addError('hfno', 'This Operator does not exist');
        $this->hfno = "";
        $this->hfname = null;
    }
}


    public function deleteRework($hfno,$type)
    {
         $hfno = trim($hfno);
    $type = trim(strtoupper($type));
        
           $this->reworkss = collect($this->reworkss)
        ->reject(fn ($rework) =>
            trim($rework['hfno'] ?? $rework['newhfno'] ?? '') === $hfno &&
            trim(strtoupper($rework['type'] ?? $rework['newtype'] ?? '')) === $type
        )
        ->values()
        ->toArray();

        $reworksData = [
        'action' => 'delete',
        'hfno'   => $hfno,
        'type'   => $type,
    ];
        $this->UpdatedNgRework();

        // Send to the other component
        $this->dispatch('FromReworks', reworksData: $reworksData);

        $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);
    }


    public function UpdatedNgRework()
    {
        // Now recalc total
        $this->totalngrework = collect($this->reworkss)
            ->sum(fn($x) => (int) $x['quan']);
    }
    public function updateRework()
    {
        foreach ($this->reworkss as &$rework) {
            if ($rework['type'] === $this->editingType) {
                $rework['quan'] = $this->newQuan;
                $rework['hfno'] = $this->hfno;
                $rework['totalinsp'] = $this->totalInsp;
                break;
            }
        }
        $this->UpdatedNGRework();

        $reworksData = [
            'newhfno' => $this->hfno,
            'newtype' => $this->editingType,
            'newquan' => $this->newQuan,
            'totalinsp' => $this->totalInsp,
            'action' => 'update'

        ];

        $this->dispatch('FromReworks', reworksData: $reworksData);

        $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);

        $this->editingType = null;
        $this->newQuan = '';
        $this->totalInsp = '';
    }

    public function startEdit($type)
    {
        $this->editingType = $type;

        // Find the rework by type
        $rework = collect($this->reworkss)->firstWhere('type', $type);

        if ($rework) {
            $this->hfno = $rework['hfno'];
            $this->newQuan = $rework['quan'];
            $this->totalInsp = $rework['totalinsp'];
        }
    }
}
