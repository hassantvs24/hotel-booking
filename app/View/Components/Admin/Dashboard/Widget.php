<?php

namespace App\View\Components\Admin\Dashboard;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Widget extends Component
{
    public $title;
    public $icon;
    public $value;
    public $iconBgClass;
    public $iconClass;
    public $description;

    /**
     * Create a new component instance.
     */
    public function __construct($title = null, $value = null, $icon = null, $description = null, $iconBgClass = null, $iconClass = null)
    {
        $this->title = $title;
        $this->icon = $icon;
        $this->value = $value;
        $this->description = $description;
        $this->iconBgClass = $iconBgClass;
        $this->iconClass = $iconClass;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.dashboard.widget');
    }
}
