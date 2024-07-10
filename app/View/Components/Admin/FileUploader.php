<?php

namespace App\View\Components\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FileUploader extends Component
{
    protected $name;
    protected $label;
    protected $value;
    protected $multiple;
    protected $accept;

    /**
     * Create a new component instance.
     */
    public function __construct($name, $label, $value = null, $multiple = false, $accept = 'image/*')
    {
        $this->name = $name;
        $this->label = $label;
        $this->value = $value;
        $this->multiple = $multiple;
        $this->accept = $accept;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.file-uploader');
    }
}
