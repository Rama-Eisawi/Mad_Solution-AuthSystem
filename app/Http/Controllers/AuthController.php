<?php

namespace App\Http\Controllers;

use App\Events\UserSignedUp;
use App\Http\Requests\Auth\loginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\signupRequest;
use App\Enums\TokenAbility;
use App\Exceptions\authExceptions;
use App\Listeners\SendVerificationCode;
use App\Traits\FilesTrait;
use App\Traits\ResponsesTrait;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    use FilesTrait;
    use ResponsesTrait;
    public function signup(signupRequest $request)
    {
        $user = User::create([
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Initialize the variables for file paths
        $photoname = null;
        $filename = null;

        // Handle profile_photo upload
        if ($request->hasFile('profile_photo')) {
            $profilePhoto = $request->file('profile_photo');
            $photoname = $this->uploadFile($profilePhoto, 'profile_photos', 'ProfilePhoto_' . $user->id);
        }

        // Handle certificate upload
        if ($request->hasFile('certificate')) {
            $certificate = $request->file('certificate');
            $filename = $this->uploadFile($certificate, 'files', 'CertificateFile_' . $user->id);
        }
        // Update the user with the file paths
        $user->profile_photo = $photoname;
        $user->certificate = $filename;
        $user->save();

        // Dispatch the UserSignedUp event
        event(new UserSignedUp($user));

        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

        return $this->sendSuccess($user, 'User Signup successfully', 201, $accessToken->plainTextToken, $refreshToken->plainTextToken);
    }

    //-----------------------------------------------------------------------------------------
    public function login(loginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            //throw new authExceptions('User not found', 404);
            return $this->sendFail('User not found', 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendFail('Invalid password', 422);
        }
        if ($user->phone_number !== $request->phone_number) {
            return $this->sendFail('Phone number does not match', 422);
        }
        if ($user->email_verified_at == null) {
            return $this->sendFail('Please verify your email', 401);
        } else {
            $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
            $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));
            $user->save();
            return $this->sendSuccess($user, 'User Loged In successfully', 200, $accessToken->plainTextToken, $refreshToken->plainTextToken);
        }
    }

    //-----------------------------------------------------------------------------------------
    public function logout(Request $request)
    {
        if (!$request) {
            return $this->sendFail('Unauthenticated', 401);
        }
        $request->user()->tokens()->delete();
        return $this->sendSuccess(null, 'Tokens Revoked', 200, null, null);
    }
    //-----------------------------------------------------------------------------------------
    public function refreshToken(Request $request)
    {
        //!Auth::check()-!auth()->check()-!auth()->guard('api')->check()-!Auth::guard('api')->check()
        $user = Auth::user();
        if (!$user) {
            // Handle unauthenticated status
            return $this->sendFail('Unauthenticated user', 401);
        }
        // Revoke all existing tokens for the user
        $request->user()->tokens()->delete();

        // Create a new access token
        $refreshToken = $request->user()->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));

        // Return response with the new token
        return $this->sendSuccess(null, 'Token generated', 200, null, $refreshToken->plainTextToken);
    }
    //------------------------------------------------------------------------
    public function sendVerificationCode(Request $request)
    {
        try {
            $ipAddress = $request->ip();
            $email = Cache::get($ipAddress . '_email');
            $verificationCode = Cache::get($ipAddress);
            if (!$email) {
                return response()->json(['message' => 'Email not found for the provided IP address.'], 404);
            }
            // Generate verification code and expiration time

            $newVerificationCode = Str::random(6);
            $expirationTime = now()->addMinutes(3);
            //Store new verification code in cache storage
            Cache::put($ipAddress, $newVerificationCode, $expirationTime);
            //Send verification code to user email
            Mail::to($email)->send(new VerificationCodeMail($newVerificationCode));

            return response()->json(['message' => 'Verification code sent successfully']);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }
    //------------------------------------------------------------------------
    //with cache
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string|max:6',
        ]);

        $verificationCode = $request->input('verification_code');
        $ipAddress = $request->ip();
        $storedCode = Cache::get($ipAddress);
        $email = Cache::get($ipAddress . '_email');
        if ($storedCode && $storedCode === $verificationCode) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->email_verified_at = now();
                $user->save();

                // Remove the verification code from the cache
                Cache::forget($ipAddress);

                return response()->json(['message' => 'Email verified successfully.']);
            } else {
                return response()->json(['error' => 'User not found.'], 404);
            }
        } else {
            return response()->json(['error' => 'Invalid or expired verification code.'], 400);
        }
    }
    //------------------------------------------------------------------------
}
