<?php

namespace App\Listeners;

use App\Events\UserSignedUp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
    public function handle(UserSignedUp $event)
    {
        $user = $event->user;
        $ipAddress = request()->ip();
        // Generate verification code and expiration time
        $verificationCode = Str::random(6);
        $expirationTime = now()->addMinutes(3);

        //Store verification code in cache storage
        Cache::put($ipAddress . '_email', $user->email);
        Cache::put($ipAddress, $verificationCode, $expirationTime);

        //Send verification code to user email
        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

        return response()->json(['message' => 'Verification code sent successfully']);
    }
}
