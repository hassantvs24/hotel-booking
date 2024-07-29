<?php

namespace App\View\Components\Portal\Shared;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Filter extends Component
{
    public $title;
    /**
     * Create a new component instance.
     */
    public function __construct($title = null)
    {
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.portal.shared.filter');
    }
}
