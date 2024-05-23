<?php

namespace App\Listeners;

use App\Events\UserSignedUp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Str;

class SendVerificationCode
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserSignedUp $event): void
    {
        $user = $event->user;
        $verificationCode = Str::random(6);
        $expirationTime = now()->addMinutes(3);

        // Save the verification code and expiration time to the user
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = $expirationTime;
        $user->save();

        // Send the verification code via email
        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));
    }
}
