<?php

namespace App\Livewire\Pages\Gl;

use App\Services\DefectService;
use App\Services\PPFService;
use App\Services\ReworkService;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Traits\NormalizeSmallDefects;
use App\Traits\NormalizeDefects;

class Gldashboard extends Component
{
    use NormalizeSmallDefects;
    use NormalizeDefects;
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

    public $rework = [];
    public $plant;

    public $locked = false;

    public $auto;
    public bool $inspectorsDispatched = false;

    public $totalngrework;
    public $hfno1, $hfno2, $hfno3, $hfno4, $hfno5;
    public $autoAdd  = false;
    public $alrdySelct = false;
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

    public function GoToHF()
    {
        return redirect()->route('hf.dashboard');
    }


    #[On('actionTable')]
    public function actioninTable($data)
    {
        if ($data['actiondash'] === 'Add') {
            $this->setAction('Add', true);
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
            $this->rework = collect($this->rework)
                ->reject(
                    fn($r) =>
                    $r['hfno'] === $normalized['hfno'] &&
                        $r['type'] === $normalized['type']
                )
                ->values()
                ->toArray();
        } else {
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
                  $this->dispatch('addbutton');
                $this->dispatch('ProgDis'); //deactivate the auto progress in check ppf
              
                $this->dispatch('EditAction', 'Add');
                $this->dispatch('locked', false);
                $this->ClearForm();
                $this->alrdySelct = true;
                break;

            case 'Edit':
                $this->dispatch('ProgDis');
                $this->dispatch('editbutton');
                $this->dispatch('EditAction', 'Edit');
                $this->dispatch('locked', false);
                $this->ClearForm();
                $this->alrdySelct = true;
                break;

            case 'Delete':
                $this->dispatch('ProgDis');
                $this->dispatch('deletebutton');
                $this->dispatch('EditAction', 'Delete');
                $this->ClearForm();
                $this->alrdySelct = true;
                break;

            case 'View':
                $this->dispatch('ProgDis');
                $this->dispatch('viewbutton');
                $this->dispatch('EditAction', 'View');
                $this->ClearForm();
                $this->alrdySelct = true;
                break;
        }

        $this->dispatch('Action', $this->submitMethod);
    }

    #[On('FetchDataGL')]
    public function FetchDatas($data)
    {
        $ppf = $this->resolvePpf($data);
        if (!$this->alrdySelct) {
            session()->flash('failed', 'Please Select Action First');
            return;
        }
        $checkppf = $this->ppfService()->checkIfPPFExist($ppf);
        if (!$checkppf) {
            session()->flash('failed', 'Record not found');
            return;
        }
        $this->dispatch('LoadMainRecord', $ppf);
        $this->LoadDefectsGL($ppf);
        $this->LoadReworksGL($ppf);
        $this->dispatch('FetchDoneRework', $ppf);
        $this->dispatch('LoadPlantGL', $ppf);
        // $this->dispatch('CalculateQuantities');
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

    public function render()
    {

        return view('livewire.pages.gl.gldashboard');
    }
}
