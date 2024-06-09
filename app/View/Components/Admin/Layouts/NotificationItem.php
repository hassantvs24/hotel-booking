<?php

namespace App\View\Components\Admin\Layouts;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationItem extends Component
{
    public $imgSrc;
    public $title;
    public $subtitle;
    public $time;
    public $svgIcon;
    public $class;

    /**
     * Create a new component instance.
     */
    public function __construct($title, $time, $imgSrc = null, $subtitle = null, $svgIcon = null, $class = '')
    {
        $this->imgSrc = $imgSrc;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->time = $time;
        $this->svgIcon = $svgIcon;
        $this->class = trim('border-radius-md ' . $class);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render() : View | Closure | string
    {
        return view('components.admin.layouts.notification-item');
    }
}
