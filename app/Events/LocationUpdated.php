<?php

namespace App\Events;

use App\Models\Location;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Location $location
     * @param float $totalKilometers
     */
    public function __construct(
        public Location $location,
        public float $totalKilometers
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('remission.' . $this->location->remission_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'LocationUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->location->id,
            'remission_id' => $this->location->remission_id,
            'ambulance_id' => $this->location->ambulance_id,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'speed' => $this->location->speed !== null ? (float) $this->location->speed : null,
            'heading' => $this->location->heading !== null ? (float) $this->location->heading : null,
            'total_kilometers' => (float) $this->totalKilometers,
            'recorded_at' => $this->location->recorded_at?->toIso8601String() ?? $this->location->created_at?->toIso8601String(),
        ];
    }
}
