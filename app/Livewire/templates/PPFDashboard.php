<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\Operator\PRInsp;
use App\Traits\InitializesInspector;
use Livewire\Component;

class PPFDashboard extends Component
{
    public $ppfdata;
    use InitializesInspector;

    public function refreshData()
    {
        $this->ppfdata = PRInsp::query()
            ->join('OperatorEnroll as oe', 'oe.OperatorID', '=', 'Inspector_PR.InspectorID')
            ->select('Inspector_PR.PPFNo')
            ->selectRaw('SUM(Inspector_PR.total_inspect) as total_inspect')
            ->selectRaw('MAX(Inspector_PR.DateEncode) as DateEncode')
            ->where('oe.GLID', $this->encoder)
            ->whereNotIn('Inspector_PR.PPFNo', function ($query) {
                $query->select('PPFNo')
                    ->from('Defect'); // table name of AddDefect
            })
            ->groupBy('Inspector_PR.PPFNo')
            ->get()
            ->map(function ($item) {
                $hf = CheckHF::where('流動NO', (int)$item->PPFNo)->first();
                $item->expct = $hf ? round($hf->合格数) : 0;
                return $item;
            });
    }

    public function mount()
    {
        $this->initializeInspector();
        $this->refreshData();
    }

    public function confirm_ppf($ppf)
    {
        $this->dispatch('actionTable', ['actiondash' => 'Add', 'ppf' => $ppf]);
    }

    public function render()
    {


        return view('livewire.templates.ppfdashboard', [
            'ppfdata' => $this->ppfdata
        ]);
    }
}
