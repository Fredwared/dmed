<?php

namespace App\Data\Auth\Request;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ResetPasswordRequestData extends Data
{
    public function __construct(
        #[Required, Email]
        public string $email,
    ) {}
}
