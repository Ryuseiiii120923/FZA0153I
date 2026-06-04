<?php

namespace App\Livewire\Pages\Menu;

use Livewire\Component;

class GLMenu extends Component
{
    public string $currentPage = 'dashboard';
    public array $loadedPages = ['dashboard'];

    public function setPage(string $page): void
    {
        $this->currentPage = $page;

        if (!in_array($page, $this->loadedPages)) {
            $this->loadedPages[] = $page;
        }
    }

    public function render()
    {
        return view('livewire.pages.menu.g-l-menu');
    }
}
