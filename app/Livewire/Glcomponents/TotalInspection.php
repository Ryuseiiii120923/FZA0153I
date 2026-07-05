<?php

namespace App\Livewire\GlComponents;

use App\Models\Forms\HF;
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

        $this->inspections = HF::with('worker')
            ->select('updated_at','inspId')
            ->selectRaw('SUM(total_inspect) as total_inspect')
            ->where('ppfno', $ppf)
            ->groupBy('inspId','updated_at')
            ->get();
    }
    public function locked($data)
    {
        $this->locked = $data;
    }
}
