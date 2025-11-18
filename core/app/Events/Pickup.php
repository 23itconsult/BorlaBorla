<?php 

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Pickup implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $data;
    public $chanelName;
    public $eventName;

    public function __construct($pickup,  $eventName, $data = [])
    {

        $data['pickup']     = $pickup;
        $this->data       = $data;
        $this->eventName  = $eventName;
        $this->chanelName = 'ride-' . $pickup->id;
    }

    public function broadcastOn()
    {
        return new Channel('pickup-' . $this->pickup->id);
    }

    public function broadcastAs()
    {
        return 'live-location';
    }
}

?>