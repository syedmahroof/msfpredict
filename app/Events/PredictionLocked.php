<?php

namespace App\Events;

use App\Models\Fixture;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PredictionLocked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Fixture $fixture) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('fixtures'),
            new Channel("fixture.{$this->fixture->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'fixture_id' => $this->fixture->id,
            'status' => 'locked',
        ];
    }
}
