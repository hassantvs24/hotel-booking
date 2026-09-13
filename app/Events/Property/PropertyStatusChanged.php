<?php

namespace App\Events\Property;

use App\Models\Property;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PropertyStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Property $property) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.notifications')];
    }

    public function broadcastAs(): string
    {
        return 'property.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id'       => $this->property->id,
            'type'     => 'property',
            'title'    => "Property {$this->property->status}",
            'message'  => "{$this->property->name} has been {$this->property->status}",
            'icon'     => 'bx-building',
            'color'    => $this->property->status === 'Published' ? 'green' : 'red',
            'created_at' => now()->toISOString(),
        ];
    }
}
