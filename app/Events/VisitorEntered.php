<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitorEntered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $id,
        public readonly string $label,
        public readonly string $initial,
        public readonly string $color,
        public readonly ?string $avatarUrl = null,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('visitors'),
        ];
    }

    /**
     * Define the event name for the broadcast.
     */
    public function broadcastAs(): string
    {
        return 'visitor.entered';
    }

    /**
     * Provide the payload that will be broadcast to the client.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'initial' => $this->initial,
            'color' => $this->color,
            'avatarUrl' => $this->avatarUrl,
        ];
    }
}
