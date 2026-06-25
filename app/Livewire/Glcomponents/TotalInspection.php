<?php

namespace App\Livewire\GlComponents;

use App\Models\Operator\HfForms;
use App\Models\Operator\PRInsp;
use Livewire\Attributes\On;
use Livewire\Component;

class TotalInspection extends Component
{
    public $inspections = [];
    public $totalInspect = 0;
    public $locked;
    public $listeners = [
        'locked' => 'locked',
    ];

    public function render()
    {
        return view('livewire.glcomponents.total-inspection');
    }

    #[On('FetchTotalInspectionTable')]
    public function FetchTotalInspection($ppf)
    {

        $this->inspections = HfForms::with('worker')
            ->select('updated_by', 'updated_date')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->where('PPFNo', $ppf)
            ->where('Operation', 'VI')
            ->groupBy('updated_by','updated_date')
            ->get();
    }
    public function locked($data)
    {
        $this->locked = $data;
    }
}
