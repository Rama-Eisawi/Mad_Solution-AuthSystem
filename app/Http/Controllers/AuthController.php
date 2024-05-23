<?php

namespace App\Http\Controllers;

use App\Events\UserSignedUp;
use App\Http\Requests\Auth\loginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\signupRequest;
use App\Enums\TokenAbility;
use App\Exceptions\authExceptions;
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
        $user = User::where('email', $request->input('email'))->first();
        if ($user) {
            return $this->sendFail('This Email is already in use', 422);
        } else {
            if (User::where('phone_number', $request->phone_number)->exists()) {
                return $this->sendFail('phone_number already in use', 422);
            } else {
                // Handle profile_photo upload
                if ($request->hasFile('profile_photo')) {
                    $profilePhoto = $request->file('profile_photo');
                    $photoname = $this->uploadFile($profilePhoto, 'profile_photos');
                } else {
                    $photoname = null;
                }

                // Handle certificate upload
                if ($request->hasFile('certificate')) {
                    $certificate = $request->file('certificate');
                    $filename = $this->uploadFile($certificate, 'files');
                } else {
                    $filename = null;
                }

                $user = User::create([
                    'username' => $request->username,
                    'phone_number' => $request->phone_number,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'profile_photo' => $photoname,
                    'certificate' => $filename,
                ]);
                $user->save();

                // Dispatch the UserSignedUp event
                //event(new UserSignedUp($user));

                $this->sendVerificationCode($user);

                $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
                $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

                return $this->sendSuccess($user, 'User Signup successfully', 201, $accessToken->plainTextToken, $refreshToken->plainTextToken);
            }
        }
    }

    //-----------------------------------------------------------------------------------------
    public function login(loginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        //$errors = [];
        //$status = [];
        if (!$user) {
            //throw new authExceptions('User not found', 404);
            //$errors[] = 'User not found';
            //$status[] = 404;
            return $this->sendFail('User not found', 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            //$errors[] = 'Invalid password';
            //$status[] = 422;
            return $this->sendFail('Invalid password', 422);
        }
        if ($user->phone_number !== $request->phone_number) {
            //$errors[] = 'Invalid password';
            //$status[] = 422;
            return $this->sendFail('Phone number does not match', 422);
        }
        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));
        $user->save();

        return $this->sendSuccess($user, 'User Loged In successfully', 200, $accessToken->plainTextToken, $refreshToken->plainTextToken);
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
    public function sendVerificationCode(User $user)
    {
        try {
            // Generate verification code and expiration time
            $verificationCode = Str::random(6);
            $expirationTime = now()->addMinutes(3);

            // Save verification code and expiration time in cache
            /*Cache::remember($user->id, $expirationTime, function () use ($verificationCode, $user) {
                return [
                    'email' => $user->email,
                    'v_code' => $verificationCode
                ];
            });*/
            $cacheKey = 'verification_code_' . $user->id;
            Cache::put($cacheKey, $verificationCode, $expirationTime);

            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

            return response()->json(['message' => 'Verification code sent successfully']);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }


    // Save verification code and expiration time to the user
    //$user->verification_code = $verificationCode;
    //$user->verification_code_expires_at = $expirationTime;
    //------------------------------------------------------------------------
    //with cache
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'verification_code' => 'required|string|max:6',
        ]);

        $userId = $request->input('user_id');
        $verificationCode = $request->input('verification_code');

        $cacheKey = 'verification_code_' . $userId;
        $storedCode = Cache::get($cacheKey);


        if ($storedCode && $storedCode === $verificationCode) {
            $user = User::find($userId);
            if ($user) {
                $user->email_verified_at = now();
                $user->save();

                // Remove the verification code from the cache
                Cache::forget($cacheKey);

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
