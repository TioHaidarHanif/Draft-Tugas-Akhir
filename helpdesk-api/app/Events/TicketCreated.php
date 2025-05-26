<?php
namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $creator;

    public function __construct(Ticket $ticket, User $creator)
    {
        $this->ticket = $ticket;
        $this->creator = $creator;
    }
}
