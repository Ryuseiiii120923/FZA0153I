<?php

namespace App\Livewire\Components;
use Livewire\Component;


class Pcomponent extends Component
{
    public $wireAction;
    public $submitMethod = null;
    public $currentAction = null;
    protected $listeners = [
        'FromCheckppf' => 'receivePPFData',
    ];



    public function ClearForm(){
        $this->dispatch('ClearForm');
    }
    public function setAction($action, $auto = false)
    {
        $this->currentAction = $action;

        $this->submitMethod = match ($action) {
            'Add' => 'addToDb',
            'Edit' => 'editToDb',
            'Delete' => 'deleteToDb',
            'View' => 'viewToDb'
        };

        switch ($action) {
            case 'Add':
                $this->dispatch('addbutton');
                $this->dispatch('ButtonPress', 'Add');
                $this->dispatch('EditAction', 'Add');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Edit':
                $this->dispatch('editbutton');
                $this->dispatch('EditAction', 'Edit');
                $this->dispatch('ButtonPress', 'Edit');
                $this->dispatch('locked', false);
                $this->ClearForm();
                break;

            case 'Delete':
                $this->dispatch('deletebutton');
                $this->dispatch('EditAction', 'Delete');
                $this->ClearForm();
                break;

            case 'View':
                $this->dispatch('viewbutton');
                $this->dispatch('EditAction', 'View');
                $this->ClearForm();
                break;
        }

        $this->dispatch('Action', $this->submitMethod);
    }
}
