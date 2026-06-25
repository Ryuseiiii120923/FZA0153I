<?php

namespace App\Traits;
use Livewire\Component;
use Livewire\Features\SupportValidation\HandlesValidation;
trait ClearErrors
{
    use HandlesValidation;
    public function clearErrors()
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->errorexisting = null;
    }
}