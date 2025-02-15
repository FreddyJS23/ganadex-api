<?php

namespace App\Events;

use App\Models\Servicio;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServicioHecho
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $servicio;
    /**
     * Create a new event instance.
     */
    public function __construct(Servicio $servicio)
    {
        $this->servicio = $servicio;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
