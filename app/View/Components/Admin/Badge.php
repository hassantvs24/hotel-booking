<?php

namespace App\View\Components\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Badge extends Component
{
    public string $type;
    public string $text;
    public string $icon;
    public string $size;
    /**
     * Create a new component instance.
     */
    public function __construct($type = 'success', $text = '', $size = 'sm', $icon = '')
    {
        $this->type = $type;
        $this->text = $text;
        $this->size = $size;
        $this->icon = $icon;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.badge');
    }
}
