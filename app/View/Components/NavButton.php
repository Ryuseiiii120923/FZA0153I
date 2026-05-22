<?php

namespace App\View\Components;

use Illuminate\View\Component;

class NavButton extends Component
{
    public string $page;
    public string $title;
    public ?string $currentPage;

    public function __construct($page, $title, $currentPage = null)
    {
        $this->page = $page;
        $this->title = $title;
        $this->currentPage = $currentPage;
    }

    public function render()
    {
        return view('components.nav-button');
    }
}
