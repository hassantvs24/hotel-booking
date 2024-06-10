<?php

namespace App\View\Components\Admin\Card;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardHeader extends Component
{
    public $title;
    public $icon;
    /**
     * Create a new component instance.
     */
    public function __construct($title = null, $icon = null)
    {
        $this->title = $title;
        $this->icon = $icon;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.card.card-header');
    }
}
