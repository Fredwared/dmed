<?php

namespace App\Actions\Auth;

use App\Data\Auth\Request\LoginData;
use App\Data\Auth\Response\AuthResponseData;
use App\Data\Auth\Response\TokenData;
use App\Data\Auth\Response\UserData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(LoginData $data): AuthResponseData
    {
        $user = User::where('email', $data->email)->first();

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return new AuthResponseData(
            user: UserData::fromUser($user),
            token: new TokenData(token: $token),
        );
    }
}
