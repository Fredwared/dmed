<?php

namespace App\Data\Auth\Response;

use Spatie\LaravelData\Data;

class TokenData extends Data
{
    public function __construct(
        public string $token,
        public string $type = 'bearer',
    ) {}
}
