<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Progress;

class ProgressUser implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $progress;
    public $status;

    /**
     * Create a new event instance.
     *
     * @param Progress $progress
     * @param string|null $status
     */
    public function __construct(Progress $progress, ?string $status = null)
{
    $this->progress = $progress;
    $this->status ?? $progress->status;

    // Create or update the corresponding user progress record
    Progress::firstOrCreate([
        'user_id' => $progress->user_id,
        'course_id' => $progress->course_id,
        'unit_id' => $progress->unit_id,
    ], [
        'progress' => $progress->progress,
        'status' => $progress->status,
    ]);
}
     

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('progress.'.$this->progress->id);
    }
    /**
     * The event's broadcast name.
     *
     * @return string
     */
     public function broadcastAs(): string
    {
        return 'ProgressUpdated';
    }
}