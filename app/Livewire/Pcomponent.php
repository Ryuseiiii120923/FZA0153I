<?php

namespace App\Livewire;

use App\Models\AddDefect;
use App\Models\AddRwk;
use App\Models\SmallDef;
use App\Models\WorkerName;
use Illuminate\Support\Facades\Auth as UserAuth;
use DateTime;
use Livewire\Component;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;


class Pcomponent extends Component
{
    public $ppf;
    public $lotno;
    public $partno;
    public $matno;
    public $moldno;
    public $pressno;
    public $shift;
    public $opt;
    public $expct;
    public $excssqty = '';
    public $lackqty = '';
    public $reworkqty = '';
    public $sampleqty = '';
    public $goodqty = 0;
    public $defects = [];
    public $smalldefects = [];
    public $rework = [];
    public $totalngrework;
    public $details;
    public $InspectDate;
    public $UpdateDate;
    public $plant;
    public $auto;
    public $insp1;
    public $insp2;
    public $insp3;
    public $insp4;
    public $insp5;
    public $hfno1;
    public $hfno2;
    public $hfno3;
    public $hfno4;
    public $hfno5;
    public $encoder;
    public $username;

    public $wireAction;
    public $submitMethod = null;
    public $currentAction = null;
    protected $listeners = [
        'FromCheckppf' => 'receivePPFData',
    ];


    public function receivePPFData($data)
    {
        $this->ppf   = $data['ppf'];
        $this->lotno = $data['lotno'];
        $this->partno = $data['partno'];
        $this->matno = $data['matno'];
        $this->moldno = $data['moldno'];
        $this->pressno = $data['pressno'];
        $this->shift = $data['shift'];
        $this->opt = $data['opt'];
        $this->expct = $data['expct'];
    }

    public function ClearForm(){
        $this->dispatch('ClearForm');
    }

    public function fetchData()
    {
        $fetch = AddDefect::Where('PPFNo',);
    }

    public function render()
    {
        // Combine all fields into an array
        $allData = [
            'ppf' => $this->ppf,
            'lotno' => $this->lotno,
            'partno' => $this->partno,
            'matno' => $this->matno,
            'moldno' => $this->moldno,
            'pressno' => $this->pressno,
            'shift' => $this->shift,
            'opt' => $this->opt,
            'expct' => $this->expct,
            'excssqty' => $this->excssqty,
            'lackqty' => $this->lackqty,
            'reworkqty' => $this->reworkqty,
            'sampleqty' => $this->sampleqty,
            'goodqty' => $this->goodqty,
            'defects' => $this->defects,
            'smalldefects' => $this->smalldefects,
            'rework' => $this->rework,
            'totalngrework' => $this->totalngrework,
            'details' => $this->details,
            'InspectDate' => $this->InspectDate,
            'UpdateDate' => $this->UpdateDate,
            'plant' => $this->plant,
            'auto' => $this->auto,
            'insp1' => $this->insp1,
            'insp2' => $this->insp2,
            'insp3' => $this->insp3,
            'insp4' => $this->insp4,
            'insp5' => $this->insp5,
            'hfno1' => $this->hfno1,
            'hfno2' => $this->hfno2,
            'hfno3' => $this->hfno3,
            'hfno4' => $this->hfno4,
            'hfno5' => $this->hfno5,
            'encoder' => $this->encoder,
            'username' => $this->username,
        ];

        return view('livewire.pcomponent', compact('allData'));
    }

    public function setActionAuto($action)
    {
        $this->setAction($action, true);
    }
    public function setAction($action, $auto = false)
    {
        $this->currentAction = $action;

        $this->dispatch('enable-buttons');
        $this->dispatch('Actions', $action);

        $this->submitMethod = match ($action) {
            'Add' => 'addToDb',
            'Edit' => 'editToDb',
            'Delete' => 'deleteToDb',
            'View' => 'viewToDb'
        };

        switch ($action) {
            case 'Add':
                $this->dispatch('addbutton');
                $this->dispatch('ButtonPress', 'Add');
                $this->dispatch('EditAction', 'Add');
                $this->dispatch('locked', false);
                $this->ClearForm();
                $this->dispatch('ClearFormFields');
                break;

            case 'Edit':
                $this->dispatch('editbutton');
                $this->dispatch('EditAction', 'Edit');
                $this->dispatch('ButtonPress', 'Edit');
                $this->dispatch('locked', false);
                $this->ClearForm();
                $this->dispatch('ClearFormFields');
                break;

            case 'Delete':
                $this->dispatch('deletebutton');
                $this->dispatch('EditAction', 'Delete');
                $this->dispatch('ButtonPress', 'Delete');
                $this->dispatch('locked', false);
                $this->ClearForm();
                $this->dispatch('ClearFormFields');
                break;

            case 'View':
                $this->dispatch('viewbutton');
                $this->dispatch('EditAction', 'View');
                $this->dispatch('ButtonPress', 'View');
                $this->dispatch('locked', false);
                $this->ClearForm();
                $this->dispatch('ClearFormFields');
                break;
        }

        if (!$auto) {
            // Only store action and reload if this was a manual click
            $this->dispatch('store-action', ['action' => $action]);
            $this->dispatch('reload-page');
        }

        $this->dispatch('Action', $this->submitMethod);
    }
}
