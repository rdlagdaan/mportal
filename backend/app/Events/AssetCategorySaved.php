<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;   // use ShouldBroadcast
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class AssetCategorySaved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Ensure it fires only after the DB transaction commits */
    public bool $afterCommit = true;

    /**
     * @param array $row        Minimal payload for the client (e.g. ['class_code','cat_code','cat_name','is_active'])
     * @param int   $companyId  Used to build the private channel name
     */
    public function __construct(public array $row, public int $companyId) {}

    public function broadcastOn()
    {
        // Private per-company channel
        return new PrivateChannel("company.{$this->companyId}.assets");
    }

    public function broadcastAs()
    {
        return 'asset-category.saved';
    }

    /** Payload sent to the client */
    public function broadcastWith(): array
    {
        return $this->row;
    }
}
