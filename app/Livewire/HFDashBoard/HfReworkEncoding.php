<?php

namespace App\Livewire\HFDashBoard;

use App\Models\Worker;
use App\Models\WorkerName;
use App\Services\DoneReworkService;
use App\Services\ForReworkService;
use App\Services\PPFService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;

class HfReworkEncoding extends Component
{
    public $pendingRework = [], $defects = [], $rework = [], $smalldefects = [];
    public $ppf, $totalngrework;
    public $open = false;
    public $selectedPPF = null;
    public $error;
    public $insp1, $insp2, $insp3, $insp4, $insp5;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $hfname,$hf_id, $total_inspect;
    public $encoder, $username, $successMessage, $errorMessage;
    public $defectNg, $reworkNg;

    private function ppfService(): PPFService
    {
        return $this->ppfService ?? app(PPFService::class);
    }

    private function forReworkService(): ForReworkService
    {
        return app(ForReworkService::class);
    }
    private function doneReworkService(): DoneReworkService
    {
        return app(DoneReworkService::class);
    }

    public function mount()
    {
        $pending = $this->forReworkService()->FetchForAllRework();
         $this->pendingRework = $pending->map(function($item) {
        return [
            'ppfno' => $item->PPFNo,
            'total_rework' => (int) $item->total_rework,
        ];
    })->toArray();
    
        foreach($this->pendingRework as $item){
            $this->total_inspect = $item['total_rework'];
        }
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $UserName = WorkerName::select('名前 ')->Where('社員CD', $this->encoder)->first();
        $this->username = $UserName->名前 ?? '';
    }

    public function render()
    {
        return view('livewire.hfdashboard.hf-rework-encoding');
    }

    public function confirm_ppf($ppf)
    {
        $this->selectedPPF = $ppf;
        $this->open = true;
    }

    public function CloseModal()
    {
        $this->resetModal();
    }

    private function resetModal()
    {
        $this->ppf = null;
        $this->selectedPPF = null;
        $this->dispatch('ClearDefects');
        $this->dispatch('ClearReworks');
        $this->open = false;
    }

    public function CheckHf()
    {
        if (empty($this->hf_id)) {
            $this->hfname = null;
            $this->resetErrorBag('hf_id');
            return;
        }

        $searchValue = (strlen($this->hf_id) === 2) ? ' ' . $this->hf_id : $this->hf_id;
        $hf = Worker::where('作業員CD', $searchValue)->first();

        if ($hf) {
            $name = WorkerName::where('社員CD', $hf->社員CD)->first();
            $this->hfname = $name ? $name->名前 : null;
            $this->resetErrorBag('hf_id');
            $this->dispatch('transferHf', [
                'total_inspect' => $this->total_inspect,
                'hf_id' => $this->hf_id
            ]);
        } else {
            $this->addError('hf_id', 'This Operator does not exist');
            $this->hf_id = "";
            $this->hfname = null;
        }

        
    }

    #[On('FromDefects')]
    public function Defects($payload = [])
    {
        if (!$payload) return;
        $defectData = $payload['defectData'] ?? $payload;

        if (isset($defectData['newDefect'])) {
            $defectData = [$defectData];
        }

        $normalized = [];
        foreach ($this->defects as $def) {
            $type = trim($def['type'] ?? '');
            $qty  = (float)($def['qty'] ?? 0);
            if ($type === '') continue;

            $key = strtolower($type);
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
            } else {
                $normalized[$key] = [
                    'type' => $type,
                    'qty'  => $qty
                ];
            }
        }

        foreach ($defectData as $data) {
            $newDefect = trim($data['newDefect'] ?? '');
            $newQuan   = (float)($data['newQuan'] ?? 0);
            $action    = $data['action'] ?? 'add';

            if (!$newDefect) continue;

            $key = strtolower($newDefect);


            if ($action === 'delete') {
                unset($normalized[$key]);
            } elseif ($action === 'update') {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            } elseif ($action === 'add') {
                if (isset($normalized[$key])) {
                    $normalized[$key]['qty'] += $newQuan;
                } else {
                    $normalized[$key] = [
                        'type' => $newDefect,
                        'qty'  => $newQuan
                    ];
                }
            } else {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }
        $this->defects = array_values($normalized);
    }

    #[On('FromReworks')]
    public function Reworks(array $reworksData)
    {
        // If the data is nested under 'reworksData', use it
        $data = $reworksData['reworksData'] ?? $reworksData;


        $type = $data['newtype'] ?? $data['type'] ?? null;
        if (!$type) return;
        // Normalize
        $normalized = [
            'hfno'      => $data['newhfno'] ?? $data['hfno'] ?? '',
            'type'      => strtoupper(trim($type)),
            'quan'      => (int) ($data['newquan'] ?? $data['quan'] ?? 0),
            'totalinsp' => (int) ($data['totalinsp'] ?? 0),
        ];

        // Ensure $this->rework is an array
        $this->rework = $this->rework ?? [];

        if (($data['action'] ?? '') === 'delete') {
            // Remove matching rework
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->values()
                ->toArray();
        } else {
            // Add or update
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->push($normalized)
                ->values()
                ->toArray();
        }

        // Recalculate total quantity
        $this->totalngrework = collect($this->rework)->sum('quan');

        $hfnos = array_column($this->rework, 'hfno');
        $this->hfno1 = $hfnos[0] ?? '';
        $this->hfno2 = $hfnos[1] ?? '';
        $this->hfno3 = $hfnos[2] ?? '';
        $this->hfno4 = $hfnos[3] ?? '';
        $this->hfno5 = $hfnos[4] ?? '';
    }


    #[On('FromReworksData')]
    public function ReworksData($data)
    {
        $this->totalngrework = $data['totalngrework'];
    }

    #[On('FromSmallDefects')]
    public function SmallDefects($smalldefectData)
    {
        $large  = $smalldefectData['SelectedLargeDefect'];
        $type   = $smalldefectData['type'] ?? $smalldefectData['newSmallDefect'];
        $qty    = $smalldefectData['qty'] ?? $smalldefectData['newSmallQuan'];
        $action = $smalldefectData['action'] ?? 'add';

        if (!isset($this->smalldefects[$large])) {
            $this->smalldefects[$large] = [];
        }

        // Normalize existing small defects by lowercase type
        $normalized = [];
        foreach ($this->smalldefects[$large] as $small) {
            $smallType = strtolower($small['type'] ?? '');
            if ($smallType === '') continue;

            if (isset($normalized[$smallType])) {
                $normalized[$smallType]['qty'] += $small['qty'];
            } else {
                $normalized[$smallType] = [
                    'type' => $small['type'],
                    'qty'  => $small['qty']
                ];
            }
        }

        $key = strtolower($type);

        if ($action === 'delete') {
            // Remove the small defect
            //dd('here');
            unset($normalized[$key]);
        } elseif ($action === 'update') {
            // Update the quantity if it exists
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] = $qty;
            }
        } else {
            // Add new small defect
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
            } else {
                $normalized[$key] = [
                    'type' => $type,
                    'qty'  => $qty
                ];
            }
        }

        // Save back normalized array
        $this->smalldefects[$large] = array_values($normalized);
    }

    #[On('sendNg')]
    public function fetchDefectNg($data)
    {
        $this->defectNg = $data;
    }

    #[On('sendNgRework')]
    public function fetchReworkNg($data)
    {
        $this->reworkNg = $data;
    }

    public function saveHF($ppf)
    {
        try {
           
            $goodQty = ($this->total_inspect ?? 0) - ($this->defectNg ?? 0) - ($this->reworkNg ?? 0);
            $data = [
                'hf_id' => $this->hf_id ?? null,
                'total_inspect' => $this->total_inspect ?? 0,
                'encoder' => $this->encoder ?? 'system',
                'ppfno' => $ppf,
                'goodQty' => $goodQty,
                'defects' => $this->defects ?? [],
                'reworks' => $this->rework ?? [],
                'smalldefects' => $this->smalldefects ?? [],
            ];

            $this->doneReworkService()->saveDoneRework($data);

            $this->resetModal();
           session()->flash('success', 'Saved Successfully!');
        } catch (\Throwable $e) {
           $this->errorMessage = $e->getMessage();
        }
    }
}
