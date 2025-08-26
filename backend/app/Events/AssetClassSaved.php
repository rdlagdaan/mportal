<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;   // ⬅️ use this
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class AssetClassSaved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** Broadcast only after the DB transaction commits */
    public bool $afterCommit = true;                     // ⬅️ add this

    public function __construct(public array $row, public int $companyId) {}

    public function broadcastOn() { return new PrivateChannel("company.{$this->companyId}.assets"); }
    public function broadcastAs() { return 'asset-class.saved'; }

    // optional payload
    public function broadcastWith(): array { return $this->row; }
}
