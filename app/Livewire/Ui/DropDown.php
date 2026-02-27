<?php

namespace App\Livewire\Ui;

use Livewire\Component;

class DropDown extends Component
{
    public $forms = [];

    public function addNew()
    {
        $uniqueId = uniqid();
        $this->forms[$uniqueId] = [
            'hf_id' => '',
            'total_inspect' => '',
            'open' => false, // start expanded by default
            'defects' => [],
            'rework' => [],
        ];
    }

    public function toggle($index)
    {
        $this->forms[$index]['open'] = !$this->forms[$index]['open'];
    }
    public function remove($id)
    {
        unset($this->forms[$id]);
        $this->forms = array_values($this->forms);
    }
    public function render()
    {
        return view('livewire.ui.drop-down');
    }
}
