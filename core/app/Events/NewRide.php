<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewRide implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $chanelName;
    public $eventName;
    public $data;

    public function __construct($chanelName, $data, $eventName = "driver-new-ride")
    {
        $this->chanelName = $chanelName;
        $this->eventName  = $eventName;
        $this->data       = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new PrivateChannel($this->chanelName);
    }

    public function broadcastAs()
    {
        return $this->eventName;
    }
}
