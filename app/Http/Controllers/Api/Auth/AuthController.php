<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\SendResetLinkAction;
use App\Data\Auth\Request\LoginData;
use App\Data\Auth\Request\RegisterData;
use App\Data\Auth\Request\ResetPasswordData;
use App\Data\Auth\Request\ResetPasswordRequestData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterData $data, RegisterAction $action): JsonResponse
    {
        return response()->json($action->execute($data), 201);
    }

    public function login(LoginData $data, LoginAction $action): JsonResponse
    {
        return response()->json($action->execute($data));
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(ResetPasswordRequestData $data, SendResetLinkAction $action): JsonResponse
    {
        $action->execute($data);

        return response()->json(['message' => 'Password reset link sent']);
    }

    public function resetPassword(ResetPasswordData $data, ResetPasswordAction $action): JsonResponse
    {
        $action->execute($data);

        return response()->json(['message' => 'Password reset successfully']);
    }
}
