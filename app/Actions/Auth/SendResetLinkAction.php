<?php

namespace App\Actions\Auth;

use App\Data\Auth\Request\ResetPasswordRequestData;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class SendResetLinkAction
{
    public function execute(ResetPasswordRequestData $data): void
    {
        $status = Password::sendResetLink(['email' => $data->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }
}
