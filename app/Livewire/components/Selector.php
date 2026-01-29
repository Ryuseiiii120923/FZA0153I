<?php

namespace App\Livewire\Components;

use Livewire\Component;

class Selector extends Component
{
    public function render()
    {
        return view('livewire.components.selector');
    }

    public function ProcessRecord(){
       
        // return redirect()->route('operator.dashboard', ['systemname' => 'ProcessRecord']);
        return redirect()->route('prencode', ['systemname' => 'ProcessRecord']);
    }
}
