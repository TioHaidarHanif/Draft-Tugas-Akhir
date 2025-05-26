<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\TicketCreated::class => [
            \App\Listeners\SendNewTicketNotification::class,
        ],
        \App\Events\TicketAssigned::class => [
            \App\Listeners\SendAssignmentNotification::class,
        ],
        \App\Events\TicketStatusChanged::class => [
            \App\Listeners\SendStatusChangeNotification::class,
        ],
        \App\Events\TicketFeedbackAdded::class => [
            \App\Listeners\SendFeedbackNotification::class,
        ],
    ];
}
