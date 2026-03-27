<?php

namespace App\Livewire\GlComponents;

use App\Models\Operator\PRInsp;
use Livewire\Attributes\On;
use Livewire\Component;

class TotalInspection extends Component
{
    public $inspections = [];
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
       $this->inspections = PRInsp::with(['worker.employeeName'])
    ->where('PPFNo', $ppf)
    ->get();
    }
     public function locked($data)
    {
        $this->locked = $data;
    }
}
