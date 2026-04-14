<?php

namespace App\Livewire\Templates;

use App\Services\ForReworkService;
use App\Services\PPFService;
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
    public $lastGoodQty = 0;
    public $locked = false;
    public $locklack = false;
    public $action;
    public $ppfService;
    public $ppf;
    public $lastexcssqty = 0;
    public $lastlackqty = 0;
    public $lastreworkqty = 0;
    public $lastsampleqty = 0;
    public $initialGoodQty = 0;

    private function ForReworkService(): ForReworkService
    {
        return $this->ppfService ?? app(ForReworkService::class);
    }

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
        $this->ppf = (int) $data['ppf'];
        $this->excssqty   = (int) ($data['excssqty']   ?? $this->excssqty   ?? 0);
        $this->lackqty    = (int) ($data['lackqty']    ?? $this->lackqty    ?? 0);
        $this->reworkqty  = (int) ($data['reworkqty']  ?? $this->reworkqty  ?? 0);
        $this->sampleqty  = (int) ($data['sampleqty']  ?? $this->sampleqty  ?? 0);
        $this->ngratioqty = (int) ($data['ngratioqty'] ?? $this->ngratioqty ?? 0);
        $this->expct      = (int) ($data['expct']      ?? $this->expct      ?? 0);
        $this->goodqty = $this->ForReworkService()->fetchGoodQty($this->ppf);
        // $this->GoodNg();
        // $this->fetchGoodQty($this->ppf);
    }

    #[On('fetchGoodQty')]
    public function fetchGoodQty($ppf)
    {
        $this->initialGoodQty = (float) $this->ForReworkService()->fetchGoodQty($ppf);
        $this->goodqty = $this->initialGoodQty;
        $this->GoodNg();
    }

    #[On('UpdateQty')]
    public function UpdateQty($data)
    {
        $this->excssqty   = (int) ($data['excssqty']   ?? $this->excssqty   ?? 0);
        $this->lackqty    = (int) ($data['lackqty']    ?? $this->lackqty    ?? 0);
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
        $this->GoodNg();
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
        return view('livewire.templates.goodng');
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

    #[On('EditAction')]
    public function Action($data)
    {
        $this->action = $data;
    }

    public function GoodNg()
    {
        // Convert empty strings to 0
        $this->excssqty  = $this->excssqty ?: 0;
        $this->lackqty   = $this->lackqty ?: 0;
        $this->reworkqty = $this->reworkqty ?: 0;
        $this->sampleqty = $this->sampleqty ?: 0;
        // Lock lack if excess exists
        if ($this->excssqty != 0) {
            $this->locklack = true;
            $this->lackqty = 0;
        } else {
            $this->locklack = false;
        }

        if ($this->excssqty == 0 && $this->lackqty == 0 && $this->reworkqty == 0 && $this->sampleqty == 0) {
            $this->goodqty = $this->initialGoodQty;
            $denominator = $this->goodqty + $this->TotalNg;
            $this->ngratioqty = $denominator == 0
                ? 0
                : number_format(($this->TotalNg / $denominator) * 100, 2);
        }

        // Skip recalculation if nothing changed
        if (
            $this->lastexcssqty == $this->excssqty &&
            $this->lastlackqty == $this->lackqty &&
            $this->lastreworkqty == $this->reworkqty &&
            $this->lastsampleqty == $this->sampleqty

        ) {
            return;
        }



        // Save last values
        $this->lastexcssqty  = $this->excssqty;
        $this->lastlackqty   = $this->lackqty;
        $this->lastreworkqty = $this->reworkqty;
        $this->lastsampleqty = $this->sampleqty;

        // ✅ Always compute from DB base
        $this->goodqty = $this->initialGoodQty
            + $this->excssqty
            - $this->lackqty
            - $this->reworkqty
            - $this->sampleqty;

        // NG ratio
        $denominator = $this->goodqty + $this->TotalNg;
        $this->ngratioqty = $denominator == 0
            ? 0
            : number_format(($this->TotalNg / $denominator) * 100, 2);

        $this->dispatch('FromGoodNg', [
            'goodqty'    => $this->goodqty,
            'ngratioqty' => $this->ngratioqty,
            'excssqty'   => $this->excssqty,
            'lackqty'    => $this->lackqty,
            'reworkqty'  => $this->reworkqty,
            'sampleqty'  => $this->sampleqty
        ]);
    }

    #[On('UpdateGoodQty')]
    public function UpdateGoodQty($data)
    {
        $diff = $data - $this->lastGoodQty;
        $this->goodqty += $diff;

        $this->lastGoodQty = $data;

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
