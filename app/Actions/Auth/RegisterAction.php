<?php

namespace App\Actions\Auth;

use App\Data\Auth\Request\RegisterData;
use App\Data\Auth\Response\AuthResponseData;
use App\Data\Auth\Response\TokenData;
use App\Data\Auth\Response\UserData;
use App\Models\User;

class RegisterAction
{
    public function execute(RegisterData $data): AuthResponseData
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return new AuthResponseData(
            user: UserData::fromUser($user),
            token: new TokenData(token: $token),
        );
    }
}
