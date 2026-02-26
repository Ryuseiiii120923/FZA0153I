<?php

namespace App\Livewire\Templates;

use App\Models\DefectInsp;
use App\Models\ReworkInsp;
use App\Models\SmallInsp;
use App\Models\Worker;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;
use Livewire\Attributes\On;

class Operatordash extends Component
{
    public $ppf;
    public $dateEncode;
    public $encoder, $inspectorID;
    public $defects = [];
    public $smalldefects = [];
    public $lastdef;
    public $lastqty;
    public $ppfrecord = [];

    public function mount()
    {
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
         $inspectorID = Worker::select('作業員CD')->Where('社員CD', $this->encoder)->first();
        $this->inspectorID = $inspectorID -> 作業員CD;
    }

    public function render()
    {
        return view('livewire.templates.operatordash');
    }


    #[On('LoadDash')]
    public function LoadPPF()
{
    $defect = DefectInsp::select('PPFNo', 'DateEncode')
        ->where('InspectorID', $this->inspectorID);

    $rework = ReworkInsp::select('PPFNo', 'DateEncode')
        ->where('InspectorID', $this->inspectorID);

    $ppfrecord = $defect
        ->unionAll($rework)
        ->get();

    $this->ppfrecord = $ppfrecord->isNotEmpty() ? $ppfrecord : [];
}

    public function editPPF($ppf) {
        $this->dispatch("dash-ppf", [
            'ppf' => $ppf,
            'actiondash' => 'edit',
            'encoder' => $this->inspectorID
            ]);
    }

     public function deletePPF($ppf){
       $this->dispatch('DeletePPFPren', [ 'ppf' => $ppf]);

    }
}
