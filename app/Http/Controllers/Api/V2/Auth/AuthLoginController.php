<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Admin\UserLog;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthLoginController extends Controller
{
    use HttpResponses;

    private const PLAYER_ROLE = 2;

    private const ADMIN = 1;

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('phone', 'password');

        $user = User::where('phone', $request->phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('', 'Credential does not match!', 401);
        }

        if ($user->is_changed_password == 0) {
            return $this->error($user, 'You have to change password', 200);
        }

        if (! Auth::attempt($credentials)) {
            return $this->error('', 'Credentials do not match!', 401);
        }

        $user = User::where('phone', $request->phone)->first();
        if (! $user->hasRole('Player')) {
            return $this->error('', 'You are not a player!', 401);
        }

        // Revoke all previous tokens for the user
        $user->tokens()->delete();

        // Create a new token for the current session
        $token = $user->createToken('authToken')->plainTextToken;

        UserLog::create([
            'ip_address' => $request->ip(),
            'user_id' => $user->id,
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ], 'User logged in successfully.');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'phone' => [
                'required',
                'regex:/^(09)[0-9]{7,11}$/',
                'numeric',
                'unique:users,phone',
            ],
            'password' => ['required', 'confirmed'],
        ]);

        $user = User::create([
            'user_name' => $this->generateRandomString(),
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'agent_id' => self::ADMIN,
            'type' => UserType::Player,
        ]);
        $user->roles()->sync(self::PLAYER_ROLE);

        return $this->success(new UserResource($user), 'User registered successfully.');
    }

    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();

        return $this->success([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function getUser()
    {
        return $this->success(new UserResource(Auth::user()), 'User success');
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $player = Auth::user();
        if (Hash::check($request->current_password, $player->password)) {
            $player->update([
                'password' => Hash::make($request->password),
                'status' => 1,
            ]);
        } else {
            return $this->error('', 'Old password is incorrect', 401);
        }

        return $this->success($player, 'Password has been changed successfully.');
    }

    public function playerChangePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed'],
            'user_id' => ['required'],
        ]);
        $player = User::where('id', $request->user_id)->first();

        if ($player) {
            $player->update([
                'password' => Hash::make($request->password),
                'is_changed_password' => true,
            ]);

            return $this->success($player, 'Password has been changed successfully.');
        } else {
            return $this->error('', 'Player not found', 401);
        }
    }

    public function profile(ProfileRequest $request)
    {
        $player = Auth::user();
        $player->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return $this->success(new UserResource($player), 'Profile updated successfully.');
    }

    private function generateRandomString()
    {
        $randomNumber = mt_rand(10000000, 99999999);

        return 'w-'.$randomNumber;
    }
}
