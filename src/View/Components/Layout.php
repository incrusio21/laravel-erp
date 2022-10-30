<?php

namespace Erp\View\Components;

use Illuminate\View\Component;

class Layout extends Component
{
    /**
     * The alert class.
     *
     * @var string
     */
    public $class;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($class = false)
    {
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('erp::master');
    }
}
