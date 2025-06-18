<?php

namespace Tests\Unit;

use App\Mail\ManualMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ManualMailTest extends TestCase
{
    /** @test */
    public function it_builds_the_email_with_subject_and_body()
    {
        $subject = 'Test Subject';
        $body = 'This is the body.';
        $mailable = new ManualMail($subject, $body);
        $mailable->build();

        $this->assertEquals($subject, $mailable->subjectText);
        $this->assertEquals($body, $mailable->bodyText);
    }
}
