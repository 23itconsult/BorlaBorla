<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WastePickup implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */

    public $data;
    public $chanelName;
    public $eventName;

    public function __construct($wastePickup, $eventName, $data = [])
    {
        $data['waste_pickup']     = $wastePickup;
        $this->data       = $data;
        $this->eventName  = $eventName;
        $this->chanelName = 'waste_pickup-' . $wastePickup->id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * 
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
