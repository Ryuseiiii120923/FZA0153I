<?php

namespace App\Livewire;

use App\Models\Rework as ModelsRework;
use App\Models\Worker;
use App\Models\WorkerName;
use Livewire\Component;
use Illuminate\Support\Collection;

class Rework extends Component
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

       public function locked($data){
        $this->locked = $data;
    }
    public function render()
    {
        $rework = ModelsRework::all();
        return view('livewire.rework', [
            'rework' => $rework,
        ]);
    }
    public function ClearForm(){
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

        $normalizedNewDefect = strtoLower(trim($this->newRework));
        $this->reworkcheck = ModelsRework::query()
        ->select('DefectType')
        ->distinct()
        ->whereNotNull('DefectType')
        ->orderBy('DefectType', 'ASC')
        ->get();

        $existsInMaster = $this->reworkcheck
        ->pluck('DefectType')
        ->map(fn($d) => strtolower(trim($d))) // normalize
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

        $isnewHf = !collect($this->reworkss)->pluck('hfno')->contains($this->hfno);
        if ($isnewHf && $uniquehf >= 5) {
            $this->addError('hfno', 'You can only add up to 5 HF Number');
            return;
        }

        if ($existing) {
            $this->addError('newRework', 'This rework is already existing');
            return;
        }
        $this->reworkss[] = [
            'hfno' => $this->hfno,
            'totalinsp' => $this->totalInsp,
            'type' => trim($this->newRework),
            'quan' => $this->newQuan
        ];
        $reworksData = [
            'newhfno' => $this->hfno,
            'newtype' => $this->newRework,
            'newquan' => $this->newQuan,
            'totalinsp' => $this->totalInsp,

        ];
        $this->UpdatedNGRework();
        $this->dispatch('FromReworks', reworksData: $reworksData);

        $this->dispatch('FromReworksData', [
            'totalngrework' => $this->totalngrework
        ]);
        $this->hfno = '';
        $this->totalInsp = '';
        $this->newRework = '';
        $this->newQuan = '';
    }

    public function CheckHf()
    {
        if (strlen($this->hfno) === 2) {
            $hfsp = ' ' . $this->hfno;
            $hf = Worker::where('作業員CD', $hfsp)->first();
        } else {
            $hf = Worker::where('作業員CD', $this->hfno)->first();
        }
        if ($hf) {
            $name = WorkerName::where('社員CD', $hf->社員CD)->first();
            $this->hfname = $name->名前;
            $this->resetErrorBag('hfno');
        } else {
            $this->addError('hfno', 'This Operator is not exist');
            $this->hfno = "";
        }
    }

    public function deleteRework($type)
    {
        $this->reworkss = collect($this->reworkss)
            ->reject(fn($reworks) => $reworks['type'] === $type)
            ->values()
            ->toArray();

        $this->UpdatedNgRework();
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
            $this->newQuan = $rework['quan'];
            $this->totalInsp = $rework['totalinsp'];
        }
    }
}
