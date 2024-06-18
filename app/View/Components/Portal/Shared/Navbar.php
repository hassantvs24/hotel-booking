<?php

namespace App\View\Components\Portal\Shared;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Navbar extends Component
{
    public $isHome;
    /**
     * Create a new component instance.
     */
    public function __construct($isHome = false)
    {
        $this->isHome = $isHome;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.portal.shared.navbar');
    }
}
