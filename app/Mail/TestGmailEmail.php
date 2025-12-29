<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestGmailEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function build()
    {
        return $this->subject('✓ Gmail SMTP Configuration Test - BookingGO')
                    ->view('emails.test')
                    ->with([
                        'message' => 'This is a test email from your BookingGO system. Gmail SMTP is now configured and working correctly!',
                        'timestamp' => date('Y-m-d H:i:s'),
                    ]);
    }
}
