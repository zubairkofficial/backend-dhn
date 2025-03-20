<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CounterLimitAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Benachrichtigung: Werkzeug-Limit erreicht')
                    ->view('counter_limit_alert')
                    ->with('details', $this->details);
    }
}
