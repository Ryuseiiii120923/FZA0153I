<?php

namespace App\Livewire\Templates;

use App\Models\GL\EnrollOperator;
use App\Models\Operator\DefectInsp;
use App\Models\Operator\PRInsp;
use App\Models\Operator\ReworkInsp;
use App\Models\Operator\SmallInsp;
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
    public $loading = false;

    public function mount()
    {
        $userencoder = UserAuth::user()->社員CD;
        $this->encoder = (int)$userencoder;
        $this->inspectorID = Worker::where('社員CD', $this->encoder)
            ->value('作業員CD');
    }

    public function render()
    {
        return view('livewire.templates.operatordash');
    }


    #[On('LoadDash')]
    public function LoadPPF()
    {
        $ppfrecord = PRInsp::where('InspectorID', $this->inspectorID)
            ->orderBy('DateEncode', 'desc')
            ->get();

        $this->ppfrecord = $ppfrecord->isNotEmpty() ? $ppfrecord : [];
    }

    public function editPPF($ppf)
    {
          $optExist = EnrollOperator::where('OperatorID', $this->inspectorID)->exists();

        if (!$optExist) {
            session()->flash('failed', 'Operator Not Enrolled. Please Coordinate to GL');
            return;
        }
        $this->dispatch('ClearFormDropdown');
        $this->loading = true;
        $this->dispatch("dash-ppf", [
            'ppf' => $ppf,
            'actiondash' => 'edit',
            'encoder' => $this->inspectorID
        ]);
    }

    #[On('IsLoading')]
    public function isLoading($data)
    {
        $this->loading = $data;
    }

    public function deletePPF($ppf)
    {
        $this->dispatch('DeletePPFPren', ['ppf' => $ppf]);
    }
    public function viewPPF($ppf)
    {
        $this->dispatch("dash-ppf", [
            'ppf' => $ppf,
            'actiondash' => 'View',
            'encoder' => $this->inspectorID
        ]);
        $this->dispatch('lockbuttons');
        $this->dispatch('ClearFormDropdown');
    }
}
