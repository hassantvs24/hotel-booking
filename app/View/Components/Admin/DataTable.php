<?php

namespace App\View\Components\Admin;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DataTable extends Component
{
    public $headers;
    public $rows;
    /**
     * Create a new component instance.
     */
    public function __construct($headers, $rows)
    {
        $this->headers = $headers;
        $this->rows = $rows;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.data-table');
    }
}
