<?php
namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $assignedTo;
    public $assignedBy;

    public function __construct(Ticket $ticket, User $assignedTo, User $assignedBy)
    {
        $this->ticket = $ticket;
        $this->assignedTo = $assignedTo;
        $this->assignedBy = $assignedBy;
    }
}
