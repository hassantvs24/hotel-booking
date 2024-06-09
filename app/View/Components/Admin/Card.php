<?php

namespace App\View\Components\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public $title;
    public $amount;
    public $percentage;
    public $description;
    public $iconClass;
    public $iconBgClass;
    public $additionalClasses;
    /**
     * Create a new component instance.
     */
    public function __construct($title, $amount, $percentage, $description, $iconClass, $iconBgClass, $additionalClasses = '')
    {
        $this->title = $title;
        $this->amount = $amount;
        $this->percentage = $percentage;
        $this->description = $description;
        $this->iconClass = $iconClass;
        $this->iconBgClass = $iconBgClass;
        $this->additionalClasses = $additionalClasses;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.card');
    }
}
