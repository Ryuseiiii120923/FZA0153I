<?php

namespace App\Livewire\Templates;

use App\Services\DefectService;
use App\Services\PPFService;
use App\Services\ReworkService;
use Livewire\Attributes\On;
use Livewire\Component;

class Gldashboard extends Component
{
    public $wireAction;
    public $submitMethod = null;
    public $currentAction = null;
    public $ppf;
    public $actiondash;

    public $lotno;
    public $partno;
    public $matno;

    public $encoder, $username;
    public $lastdef;
    public $lastqty;
    public $expct;
    public $goodqty;
    public $ngratioqty;
    public $totalQty;
    public $Largedefects;
    public $moldno;
    public $pressno;
    public $shift;
    public $opt;
    public $details;
    public $InspectDates;
    public $UpdateDate;
    public $excssqty;
    public $lackqty;
    public $reworkqty;
    public $sampleqty;
    public $isAdd = false;

    public $defects = [];
    public $smalldefects = [];
    public $rework = [];
    public $plant;

    public $locked = false;

    public $auto;
    public bool $inspectorsDispatched = false;

    public $totalngrework;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $autoAdd  = false;

    protected $defectService, $reworkService;

    public function defectService(): DefectService
    {
        return $this->defectService ?? app(DefectService::class);
    }

    public function reworkService(): ReworkService
    {
        return $this->reworkService ?? app(ReworkService::class);
    }

    public function ppfService(): PPFService
    {
        return app(PPFService::class);
     }
    protected $listeners = [
        'FromCheckppf' => 'Checkppf',
        'FromDefects' => 'Defects',
        'FromSmallDefects' => 'SmallDefects',
        'FromReworks' => 'Reworks',
        //'ClearForm' => 'ClearForm',
        'LoadDefectsPren' => 'LoadDefectsPren',
        'LoadReworksPren' => 'LoadReworksPren',
        'LoadReworksGL' => 'LoadReworksGL',
        'LoadDefectsGL' => 'LoadDefectsGL'
    ];
    #[On('dash-ppf')]
    public function action($data)
    {
        $this->actiondash = $data['actiondash'];
    }

    // temporary function to navigate to hf rework encoding page
    public function GoToHF()
    {
        return redirect()->route('hf.dashboard');
    }


    #[On('actionTable')]
    public function actioninTable($data)
    {
        if ($data['actiondash'] === 'Add') {
            $this->setAction('Add', true);
            //  $this->dispatch('ProgDis'); //deactivate the auto progress in check ppf
            //     $this->dispatch('addbutton');
            //     $this->dispatch('EditAction', 'Add');
            //     $this->dispatch('locked', false);
            //     // $this->dispatch('ppfcheck');
            //     // $this->dispatch('dash-ppf-check', $this->ppf);
            //     //  $this->dispatch('post-ppf', ['ppf' => $this->ppf]);
            //     //$this->dispatch('fromppf', $this->ppf);
            //     $this->ClearForm();
        }
        $this->ppf = $data['ppf'];

        $this->dispatch('dash-ppfGL', ['ppf' => (int)$this->ppf, 'actiondash' => $data['actiondash']]);
    }


    #[On('FromCheckppf')]
    public function FromCheckppf($data)
    {
        $this->expct = $data['expct'];
    }

    public function Reworks(array $reworksData)
    {

        $type = $reworksData['newtype'] ?? $reworksData['type'] ?? null;
        if (!$type) return;

        // Normalize once
        $normalized = [
            'hfno'      => $reworksData['newhfno'] ?? $reworksData['hfno'] ?? '',
            'type'      => strtoupper(trim($type)),
            'quan'      => (int) ($reworksData['newquan'] ?? $reworksData['quan'] ?? 0),
            'totalinsp' => (int) ($reworksData['totalinsp'] ?? 0),
        ];

        if (($reworksData['action'] ?? '') === 'delete') {
            // DELETE only matching hfno + type
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->values()
                ->toArray();
        } else {
            // ADD or UPDATE based on hfno + type
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

        // Recalculate
        $this->totalngrework = collect($this->rework)->sum('quan');
    }


    //From Adding Reworks
    public function ReworksData($data)
    {
        $this->totalngrework = $data['totalngrework'];
    }

    public function Checkppf($data)
    {
        $this->ppf = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->partno = $data['partno'];
        $this->matno = $data['matno'];
    }


    //From adding defects
    public function Defects($payload = [])
    {
        if (!$payload) return;

        $defectData = $payload['defectData'] ?? $payload;

        $newDefect = trim($defectData['newDefect'] ?? '');
        $newQuan   = (float)($defectData['newQuan'] ?? '');
        $action    = $defectData['action'] ?? 'add';

        if (!$newDefect) return;

        $normalized = [];
        foreach ($this->defects as $def) {
            $type = $def['type'] ?? $def['newDefect'] ?? '';
            $qty  = (float)($def['qty'] ?? $def['newQuan'] ?? '');

            if ($type === '') continue;

            if (isset($normalized[strtolower($type)])) {
                $normalized[strtolower($type)]['qty'] += $qty;
            } else {
                $normalized[strtolower($type)] = [
                    'type' => $type,
                    'qty'  => (int) $qty
                ];
            }
        }


        $key = strtolower($newDefect);

        if ($action === 'delete') {
            unset($normalized[$key]);
            $this->defects = array_values($normalized);
            return;
        }


        if ($action === 'update') {

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] = $newQuan;
            }
        } else {

            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $newQuan;
            } else {
                $normalized[$key] = [
                    'type' => $newDefect,
                    'qty'  => $newQuan
                ];
            }
        }

        $this->defects = array_values($normalized);
    }




    public function ClearForm()
    {
        $this->dispatch('ClearForm');
    }
    public function setAction($action, $auto = false)
    {
        $this->currentAction = $action;

        $this->submitMethod = match ($action) {
            'Add' => 'addToDb',
            'Edit' => 'editToDb',
            'Delete' => 'deleteToDb',
            'View' => 'viewToDb'
        };

        switch ($action) {
            case 'Add':
                $this->dispatch('ProgDis'); //deactivate the auto progress in check ppf
                $this->dispatch('addbutton');
                $this->dispatch('EditAction', 'Add');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Edit':
                $this->dispatch('ProgDis');
                $this->dispatch('editbutton');
                $this->dispatch('EditAction', 'Edit');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Delete':
                $this->dispatch('ProgDis');
                $this->dispatch('deletebutton');
                $this->dispatch('EditAction', 'Delete');
                $this->ClearForm();
                break;

            case 'View':
                $this->dispatch('ProgDis');
                $this->dispatch('viewbutton');
                $this->dispatch('EditAction', 'View');
                $this->ClearForm();
                break;
        }

        $this->dispatch('Action', $this->submitMethod);
    }

    #[On('FetchDataGL')]
    public function FetchDatas($data)
    {
        $ppf = $this->resolvePpf($data);

        $checkppf = $this->ppfService()->checkIfPPFExist($ppf);
        if (!($checkppf)) {
            session()->flash('failed', 'Record not found');
            return;
        }
        $this->dispatch('LoadMainRecord', $ppf);
        $this->LoadDefectsGL($ppf);
        $this->dispatch('LoadPlantGL', $ppf);
        $this->LoadReworksGL($ppf);
        $this->dispatch('CalculateQuantities');
        $this->dispatch('dispatchUpdates');
        $this->dispatch('totalInspectedProgress');
    }


    private function resolvePpf($data)
    {
        return request()->input('ppf', $data);
    }


    public function LoadDefectsGL($ppf)
    {
        $result = $this->defectService()->loadDefectsGL($ppf);

        $this->defects = $result['defects'];
        $this->smalldefects = $result['smallDefects'];

        if (count($this->defects) > 0) {

            $this->dispatch('DefectFromUpdate', [
                'defects' => $this->defects,
                'smallDefects' => $this->smalldefects,
            ]);

            $this->dispatch('FromDefects', [
                'defectData' => $result['payload']
            ]);

            $this->totalQty = $result['totalQty'];
            $this->dispatch('sendNg', $this->totalQty);

            if (!$this->inspectorsDispatched) {
                foreach ($result['inspectors'] as $id) {
                    $this->dispatch('InspectorUpdate', $id);
                }
                $this->inspectorsDispatched = true;
            }

            $this->lastdef = $result['last']['type'] ?? null;
            $this->lastqty = $result['last']['qty'] ?? null;
        }
    }


    public function LoadReworksGL($ppf)
    {
        $result = $this->reworkService()->getReworks($ppf);

        $this->rework = $result['reworks'];

        if (!empty($this->rework)) {

            $this->dispatch('ReworkFromUpdate', [
                'reworks' => $this->rework
            ]);

            $this->dispatch('FromReworks', [
                'reworksData' => $result['payload']
            ]);
        }

        $this->totalngrework = $result['total'];

        $this->dispatch('fetchGoodQty', $ppf);
    }


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

    public function render()
    {

        return view('livewire.templates.gldashboard');
    }
}
