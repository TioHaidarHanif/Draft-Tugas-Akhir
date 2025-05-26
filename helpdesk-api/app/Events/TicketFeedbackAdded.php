<?php
namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketFeedback;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketFeedbackAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $feedback;
    public $sender;

    public function __construct(Ticket $ticket, TicketFeedback $feedback, User $sender)
    {
        $this->ticket = $ticket;
        $this->feedback = $feedback;
        $this->sender = $sender;
    }
}
