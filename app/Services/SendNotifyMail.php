<?php

namespace App\Services;

use App\Mail\CounterLimitAlertMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotifyMail
{
    public function sendMail($userEmail, $details)
    {
        try {
             Mail::to($userEmail)
                ->bcc('digital-services@cretschmar.de')
                ->send(new CounterLimitAlertMail($details));
        } catch (\Exception $e) {
            // Log::error('Email sending failed: ' . $e->getMessage());
            return $e->getMessage();
        }
    }
}
