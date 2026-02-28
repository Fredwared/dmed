<?php

namespace App\Data\Auth\Response;

use Spatie\LaravelData\Data;

class AuthResponseData extends Data
{
    public function __construct(
        public UserData $user,
        public TokenData $token,
    ) {}
}
