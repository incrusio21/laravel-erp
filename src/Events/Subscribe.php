<?php
 
namespace Erp\Events;
 
class Subscribe
{
    /**
     * Handle user login events.
     */
    public function validated($event) 
    {
        if($event->method != 'validated'){
            return;
        }
        print_r($event->method);
        // app('sysdefault')->call_method($name, ['self' => $event->self, 'method' => $event->method])
    }
    
    /**
     * Handle user login events.
     */
    public function before_save($event) 
    {
        if($event->method != 'before_save'){
            return;
        }
        print_r($event->method);

    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     * @return array
     */
    public function subscribe($events)
    {
        return [
            DocEvents::class => ['validated', 'before_save'],
        ];
    }
}