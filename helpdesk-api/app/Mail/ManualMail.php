<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ManualMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $bodyText;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body)
    {
        $this->subjectText = $subject;
        $this->bodyText = $body;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subjectText)
            ->view('emails.manual')
            ->with([
                'subject' => $this->subjectText,
                'body' => $this->bodyText,
            ]);
    }
}
