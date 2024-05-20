<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\loginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\signupRequest;
use App\Enums\TokenAbility;
use App\Exceptions\authExceptions;
use App\Traits\FilesTrait;
use App\Traits\ResponsesTrait;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use FilesTrait;
    use ResponsesTrait;
    public function signup(signupRequest $request)
    {
        //check for unique
        if (User::where('email', $request->email)->exists()) {
            throw new authExceptions('Email already in use', 422);
        }
        if (User::where('phone_number', $request->phone_number)->exists()) {
            throw new authExceptions('phone_number already in use', 422);
        }

        // Handle profile_photo upload
        if ($request->hasFile('profile_photo')) {
            $profilePhoto = $request->file('profile_photo');
            $photoname = $this->uploadFile($profilePhoto, 'profile_photos');
        } else
            $photoname = null;

        // Handle certificate upload
        if ($request->hasFile('certificate')) {
            $certificate = $request->file('certificate');
            $filename = $this->uploadFile($certificate, 'files');
        } else
            $filename = null;

        $user = new User([
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_photo' => $photoname,
            'certificate' => $filename,
        ]);
        $accessToken = $user->createToken("API TOKEN")->plainTextToken;
        //fire event
        //VerifyEmailEvent::dispatch($user);

        $user->save();
        return $this->sendSuccess($user, 'User Signup successfully', 201, $accessToken, null);
    }
    //-----------------------------------------------------------------------------------------
    public function login(loginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendFail('User not found', 404);
        }
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendFail('Invalid password', 422);
        }
        if ($user->phone_number !== $request->phone_number) {
            return $this->sendFail('Phone number does not match', 422);
        }
        $accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
        $refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));
        $user->save();
        return $this->sendSuccess($user, 'User Loged In successfully', 200, $accessToken, $refreshToken);
    }

    //-----------------------------------------------------------------------------------------
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return $this->sendSuccess(null, 'Tokens Revoked', 200, null, null);
    }
    //-----------------------------------------------------------------------------------------
    public function refreshToken(Request $request)
    {
        // Revoke all existing tokens for the user
        $request->user()->tokens()->delete();

        // Create a new access token
        $accessToken = $request->user()->createToken(
            'access_token',
            [TokenAbility::ACCESS_API->value],
            Carbon::now()->addMinutes(config('sanctum.ac_expiration'))
        );

        // Return response with the new token
        return response([
            'message' => "Token generated",
            'token' => $accessToken->plainTextToken
        ]);
    }
}
//------------------------------------
