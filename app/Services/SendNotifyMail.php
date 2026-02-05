<?php
namespace App\Services;

use App\Mail\CounterLimitAlertMail;
use App\Models\User;
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
            return $e->getMessage();
        }
    }

    /**
     * Send 90% limit notification only once per threshold crossing (for any service).
     * Resets when usage drops below 90% so the user can be notified again next time.
     */
    public function sendMailIfFirstTimeAt90(User $user, array $details, bool $atOrAbove90): void
    {
        if ($atOrAbove90) {
            if ($user->usage_notified_at === null) {
                $this->sendMail($user->email, $details);
                $user->usage_notified_at = now();
                $user->save();
            }
        } else {
            $user->usage_notified_at = null;
            $user->save();
        }
    }
}
