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

/**
 * @group Authentication
 *
 * Endpoints for user registration, login, logout, and password reset.
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * Create a new user account and return an auth token.
     *
     * @unauthenticated
     *
     * @bodyParam name string required User's name. Max 255 characters. Example: John Doe
     * @bodyParam email string required User's email. Must be unique. Example: john@example.com
     * @bodyParam password string required Password. Min 8 characters. Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     *
     * @response 201 {"user":{"id":1,"name":"John Doe","email":"john@example.com","created_at":"2026-02-28T10:00:00.000000Z"},"token":{"token":"1|abc123...","type":"bearer"}}
     * @response 422 scenario="Validation error" {"message":"The email has already been taken.","errors":{"email":["The email has already been taken."]}}
     */
    public function register(RegisterData $data, RegisterAction $action): JsonResponse
    {
        return response()->json($action->execute($data), 201);
    }

    /**
     * Login
     *
     * Authenticate a user and return an auth token.
     *
     * @unauthenticated
     *
     * @bodyParam email string required User's email. Example: john@example.com
     * @bodyParam password string required User's password. Example: password123
     *
     * @response 200 {"user":{"id":1,"name":"John Doe","email":"john@example.com","created_at":"2026-02-28T10:00:00.000000Z"},"token":{"token":"1|abc123...","type":"bearer"}}
     * @response 422 scenario="Invalid credentials" {"message":"The given data was invalid.","errors":{"email":["These credentials do not match our records."]}}
     */
    public function login(LoginData $data, LoginAction $action): JsonResponse
    {
        return response()->json($action->execute($data));
    }

    /**
     * Logout
     *
     * Revoke the current access token.
     *
     * @authenticated
     *
     * @response 200 {"message":"Logged out successfully"}
     * @response 401 scenario="Unauthenticated" {"message":"Unauthenticated."}
     */
    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Forgot Password
     *
     * Send a password reset link to the given email address.
     *
     * @unauthenticated
     *
     * @bodyParam email string required User's email address. Example: john@example.com
     *
     * @response 200 {"message":"Password reset link sent"}
     * @response 422 scenario="Validation error" {"message":"The given data was invalid.","errors":{"email":["We can't find a user with that email address."]}}
     */
    public function forgotPassword(ResetPasswordRequestData $data, SendResetLinkAction $action): JsonResponse
    {
        $action->execute($data);

        return response()->json(['message' => 'Password reset link sent']);
    }

    /**
     * Reset Password
     *
     * Reset the user's password using a valid reset token.
     *
     * @unauthenticated
     *
     * @bodyParam token string required Reset token from email. Example: abc123def456
     * @bodyParam email string required User's email. Example: john@example.com
     * @bodyParam password string required New password. Min 8 characters. Example: newpassword123
     * @bodyParam password_confirmation string required Must match password. Example: newpassword123
     *
     * @response 200 {"message":"Password reset successfully"}
     * @response 422 scenario="Invalid token" {"message":"The given data was invalid.","errors":{"email":["This password reset token is invalid."]}}
     */
    public function resetPassword(ResetPasswordData $data, ResetPasswordAction $action): JsonResponse
    {
        $action->execute($data);

        return response()->json(['message' => 'Password reset successfully']);
    }
}
