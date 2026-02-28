<?php

namespace App\Data\Auth\Response;

use App\Models\User;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public CarbonImmutable $created_at,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            created_at: CarbonImmutable::parse($user->created_at),
        );
    }
}
