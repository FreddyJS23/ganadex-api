<?php

namespace App\Events;

use App\Models\Ganado;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PesajeLecheHecho
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ganado;
    /**
     * Create a new event instance.
     */
    public function __construct(Ganado $ganado)
    {
        $this->ganado = $ganado;
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
