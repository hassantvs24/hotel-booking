<?php

namespace App\View\Components\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public string $type;
    public string $size;
    public string $variant;
    public string $text;
    public string $icon;

    /**
     * Create a new component instance.
     */
    public function __construct($type = 'button', $size = 'xs', $variant = 'outline-primary', $text = '', $icon = '')
    {
        $this->type = $type;
        $this->size = $size;
        $this->variant = $variant;
        $this->text = $text;
        $this->icon = $icon;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.button');
    }
}
