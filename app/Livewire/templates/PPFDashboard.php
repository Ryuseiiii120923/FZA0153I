<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\Operator\PRInsp;
use Livewire\Component;

class PPFDashboard extends Component
{
    public $ppfdata;

    public function refreshData()
    {


        $this->ppfdata = PRInsp::select('PPFNo')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->selectRaw('MAX(DateEncode) as DateEncode')
            ->whereNotIn('PPFNo', function ($query) {
                $query->select('PPFNo')
                    ->from('Defect'); // table name of AddDefect
            })
            ->groupBy('PPFNo')
            ->get()
            ->map(function ($item) {
                $hf = CheckHF::where('流動NO', (int)$item->PPFNo)->first();
                $item->expct = $hf ? round($hf->合格数) : 0;
                return $item;
            });
    }

    public function mount()
    {
        $this->refreshData(); // initial load
    }

    public function confirm_ppf($ppf)
    {
        $this->dispatch('actionTable', ['actiondash' => 'Add', 'ppf' => $ppf]);
    }

    public function render()
    {
        $this->refreshData();

        return view('livewire.templates.ppfdashboard', [
            'ppfdata' => $this->ppfdata
        ]);
    }
}
