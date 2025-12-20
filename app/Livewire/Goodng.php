<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;


class Goodng extends Component
{
    public $expct = 0;
    public $TotalNg = 0;
    public $excssqty = 0;
    public $lackqty = 0;
    public $reworkqty = 0;
    public $sampleqty = 0;
    public $goodqty = 0;
    public $ngratioqty = 0;
    public $locked = false;
    public $locklack = false;

    protected $listeners = [
        'sendExcpt' => 'receiveExcpt',
        'sendNg' => 'receiveNg',
        'FetchGoodNg' => 'Fetch',
        'fetchtotalngrework' => 'totalngrework',
        'FromUpdate' => 'FetchngRework',
        'locked' => 'locked',
        'TriggerGoodNg' => 'GoodNg',
        'ClearForm' => 'ClearForm',
        'GoodNg' => 'GoodNg'
    ];
    public function mount()
    {
        $this->excssqty = 0;
        $this->lackqty = 0;
        $this->reworkqty = 0;
        $this->sampleqty = 0;
    }

    public function locked($data)
    {
        $this->locked = $data;
    }

    public function ClearForm()
    {
        $this->expct = 0;
        $this->TotalNg = 0;
        $this->excssqty = '';
        $this->lackqty = '';
        $this->reworkqty = '';
        $this->sampleqty = '';
        $this->goodqty = 0;
        $this->ngratioqty = 0;
    }

    public function onExcessBlur()
    {
        $this->GoodNg();
        $this->dispatchBrowserEvent('focus', ['id' => 'lack']);
    }

    public function onLackingBlur()
    {
        $this->GoodNg();
        $this->dispatchBrowserEvent('focus', ['id' => 'rework']);
    }
    public function onReworkBlur()
    {
        $this->GoodNg();
        $this->dispatchBrowserEvent('focus', ['id' => 'sample']);
    }

    public function onSampleBlur()
    {
        $this->GoodNg();
        // Sample is the last input, so no next focus needed
    }


    public function Fetch($data)
    {
        $this->excssqty = (int) $data['excssqty'];
        $this->lackqty = (int) $data['lackqty'];
        $this->reworkqty = (int) $data['reworkqty'];
        $this->sampleqty = (int) $data['sampleqty'];
        $this->ngratioqty = (int) $data['ngratioqty'];
        $this->expct = (int) $data['expct'];
        $this->GoodNg();
    }

    public function FetchngRework($data)
    {
        $this->TotalNg = (int) $data['ngratioqty'];
        $this->goodqty = (int) $data['goodqty'];
    }

    public function totalngrework($data)
    {
        $this->TotalNg = $data;
    }

    public function receiveExcpt($value)
    {
        $this->expct = $value;
    }
    public function receiveNg($value)
    {
        $this->TotalNg = $value;
    }
    public $rules = [
        'excssqty' => 'required|numeric',
        'lackqty' => 'required|numeric',
        'reworkqty' => 'required|numeric',
        'sampleqty' => 'required|numeric'
    ];

    public $messages = [
        'excssqty.required' => 'Please enter the Excess Quantity',
        'excssqty.numeric' => 'Excess Quantity Must be a valid number',

        'lackqty.required' => 'Please enter the Lacking Quantity',
        'lackqty.numeric' => 'Lacking Quantity Must be a valid number',

        'reworkqty.required' => 'Please enter the Rework Quantity',
        'reworkqty.numeric' => 'Rework Quantity Must be a valid number',

        'sampleqty.required' => 'Please enter the Sample Quantity',
        'sampleqty.numeric' => 'Sample Quantity Please enter a valid number'
    ];

    public function render()
    {
        return view('livewire.goodng');
    }

    #[On('setExcssQty')]
    public function updatedExcss($value)
    {
        $this->excssqty = $value;
    }

    #[On('setlack')]
    public function updatedLack($value)
    {
        $this->lackqty = $value;
    }

    #[On('setrework')]
    public function updatedRework($value)
    {
        $this->reworkqty = $value;
    }

    #[On('setsample')]
    public function updatedSample($value)
    {
        $this->sampleqty = $value;
    }

    public function GoodNg()
    {

        if ($this->excssqty === "") {
            $this->excssqty = 0;
        }
        if ($this->lackqty === "") {
            $this->lackqty = 0;
        }
        if ($this->reworkqty === "") {
            $this->reworkqty = 0;
        }
        if ($this->sampleqty === "") {
            $this->sampleqty = 0;
        }

        if ($this->excssqty <> 0) {
            $this->locklack = true;
            $this->lackqty = 0;
        } else {
            $this->locklack = false;
        }
        $this->validate();
        $this->goodqty = (float)$this->expct
            - (float)$this->TotalNg
            + (float)$this->excssqty
            - (float)$this->lackqty
            - (float)$this->reworkqty
            - (float)$this->sampleqty;

        // $this->ngratioqty = number_format(($this->TotalNg / ($this->goodqty + $this->TotalNg)) * 100, 2);

        $denominator = $this->goodqty + $this->TotalNg;

        if ((float) $denominator == 0) {
            $this->ngratioqty = 0;
        } else {
            $this->ngratioqty = number_format(($this->TotalNg / $denominator) * 100, 2);
        }

        $this->dispatch('FromGoodNg', [
            'goodqty' => $this->goodqty,
            'ngratioqty' => $this->ngratioqty,
            'excssqty' => $this->excssqty,
            'lackqty' => $this->lackqty,
            'reworkqty' => $this->reworkqty,
            'sampleqty' => $this->sampleqty
        ]);
    }
}
