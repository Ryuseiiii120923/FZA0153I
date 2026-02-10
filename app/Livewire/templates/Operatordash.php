<?php

namespace App\Livewire\Templates;

use App\Models\DefectInsp;
use App\Models\ReworkInsp;
use App\Models\SmallInsp;
use Livewire\Component;
use Illuminate\Support\Facades\Auth as UserAuth;
use Livewire\Attributes\On;

class Operatordash extends Component
{
    public $ppf;
    public $dateEncode;
    public $encoder;
    public $defects = [];
    public $smalldefects = [];
    public $lastdef;
    public $lastqty;
    public $ppfrecord = [];

    public function mount()
    {
        $this->encoder = UserAuth::user()->社員CD;
    }

    public function render()
    {
        return view('livewire.templates.operatordash');
    }


    #[On('LoadDash')]
    public function LoadPPF()
{
    $defect = DefectInsp::select('PPFNo', 'DateEncode')
        ->where('InspectorID', $this->encoder);

    $rework = ReworkInsp::select('PPFNo', 'DateEncode')
        ->where('InspectorID', $this->encoder);

    $ppfrecord = $defect
        ->unionAll($rework)
        ->get();

    $this->ppfrecord = $ppfrecord->isNotEmpty() ? $ppfrecord : [];
}

    public function editPPF($ppf) {
        $this->dispatch("dash-ppf", [
            'ppf' => $ppf,
            'actiondash' => 'edit',
            'encoder' => $this->encoder
            ]);
    }

     public function deletePPF($ppf){
       $this->dispatch('DeletePPFPren', [ 'ppf' => $ppf]);

    }
}
