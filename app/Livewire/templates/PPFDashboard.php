<?php

namespace App\Livewire\Templates;

use App\Models\AddDefect;
use App\Models\CheckHF;
use App\Models\PRInsp;
use Livewire\Component;

class PPFDashboard extends Component
{
    public $ppfdata;

    // optional: method to refresh data
    public function refreshData()
    {
        // $this->ppfdata = PRInsp::select('PPFNo')
        //     ->selectRaw('SUM(total_inspect) as total_inspect')
        //     ->selectRaw('MAX(DateEncode) as DateEncode')
        //     ->groupBy('PPFNo')
        //     ->get()
        //     ->map(function($item) {
        //         $hf = CheckHF::where('流動NO', (int)$item->PPFNo)->first();
        //         $item->expct = $hf ? round($hf->合格数) : 0;
        //         return $item;
        //     });

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
    // return redirect()->route('gl.dashboard', ['ppf' => $ppf, 'actiondash' => 'Add']);

    $this->dispatch('actionTable',['actiondash' => 'Add', 'ppf' => $ppf]);
}

    public function render()
    {
        // you can also refresh here if needed
        $this->refreshData(); 

        return view('livewire.templates.ppfdashboard', [
            'ppfdata' => $this->ppfdata
        ]);
    }
}
