<?php

namespace App\Livewire\Glcomponents;

use App\Services\ForReworkService;
use Livewire\Attributes\On;
use Livewire\Component;

class ForRework extends Component
{
    public $locked = false;
    public $pendingReworks = [];
    public function render()
    {
        return view('livewire.glcomponents.for-rework');
    }

    public function forReworkService(): ForReworkService
    {
        return app(ForReworkService::class);
    }

    #[On('fetchForRework')]
    public function fetchForRework($ppf)
    {
        $this->pendingReworks = $this->forReworkService()->FetchForRework($ppf);
    }

    public function ProceedRework($ppf) {
        $this->forReworkService()->ProceedRework($ppf);
        $this->fetchForRework($ppf);
    }
}
