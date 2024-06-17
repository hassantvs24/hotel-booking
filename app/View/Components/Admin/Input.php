<?php

namespace App\View\Components\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    public $type;
    public $name;
    public $id;
    public $label;
    public $value;
    public $options;
    public $multiple;

    /**
     * Create a new component instance.
     */
    public function __construct(
        $type = null,
        $name = null,
        $id = null,
        $label = null,
        $value = null,
        $options = [],
        $multiple = false
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->id = $id;
        $this->label = $label;
        $this->value = $value;
        $this->options = $options;
        $this->multiple = $multiple;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render() : View | Closure | string
    {
        return view('components.admin.input');
    }
}
