<?php

namespace Erp\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocEvents
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Order
     */
    public $self;

    public $method;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($self, $method)
    {
        $this->self = $self;
        $this->method = $method;
    }
}
