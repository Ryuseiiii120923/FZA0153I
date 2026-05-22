<?php

namespace App\Livewire\Glcomponents;

use Livewire\Component;

class Buttons extends Component
{
    public $submitMethod;
    public function setAction($action, $auto = false)
    {
        $this->dispatch('setActionGL', $action);

        $this->submitMethod = match ($action) {
            'Add' => 'addToDb',
            'Edit' => 'editToDb',
            'Delete' => 'deleteToDb',
            'View' => 'viewToDb'
        };

        switch ($action) {
            case 'Add':
                $this->dispatch('ProgDis');
                $this->dispatch('addbutton');
                $this->dispatch('EditAction', 'Add');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Edit':
                $this->dispatch('ProgDis');
                $this->dispatch('editbutton');
                $this->dispatch('EditAction', 'Edit');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Delete':
                $this->dispatch('ProgDis');
                $this->dispatch('deletebutton');
                $this->dispatch('EditAction', 'Delete');
                $this->ClearForm();
                break;

            case 'View':
                $this->dispatch('ProgDis');
                $this->dispatch('viewbutton');
                $this->dispatch('EditAction', 'View');
                $this->ClearForm();
                break;
        }

        $this->dispatch('Action', $this->submitMethod);
    }

      public function ClearForm()
    {
        $this->dispatch('ClearForm');
    }

    public function render()
    {
        return view('livewire.glcomponents.buttons');
    }
}
