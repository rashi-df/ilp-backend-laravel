<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if ($user->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive',
            ], 403);
        }

        $accessExpiryMinutes = (int) env('JWT_ACCESS_EXPIRY', 15);
        $refreshExpiryMinutes = (int) env('JWT_REFRESH_EXPIRY', 10080);

        $accessToken = $user->createToken('access-token', ['*'], now()->addMinutes($accessExpiryMinutes));
        $refreshToken = Str::random(64);

        $user->update(['refresh_token' => Hash::make($refreshToken)]);

        return response()->json([
            'accessToken' => $accessToken->plainTextToken,
            'refreshToken' => $refreshToken,
            'user' => $this->formatUser($user),
            'expiresIn' => $accessExpiryMinutes * 60,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refreshToken' => 'required|string',
        ]);

        $users = User::whereNotNull('refresh_token')->get();
        $user = null;

        foreach ($users as $u) {
            if (Hash::check($request->refreshToken, $u->refresh_token)) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired refresh token',
            ], 401);
        }

        if ($user->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive',
            ], 403);
        }

        $accessExpiryMinutes = (int) env('JWT_ACCESS_EXPIRY', 15);

        $user->tokens()->delete();
        $accessToken = $user->createToken('access-token', ['*'], now()->addMinutes($accessExpiryMinutes));
        $refreshToken = Str::random(64);

        $user->update(['refresh_token' => Hash::make($refreshToken)]);

        return response()->json([
            'accessToken' => $accessToken->plainTextToken,
            'refreshToken' => $refreshToken,
            'expiresIn' => $accessExpiryMinutes * 60,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        $request->user()->update(['refresh_token' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->formatUser($request->user()),
        ]);
    }

    private function formatUser(User $user): array
    {
        $user->load('subscription.plan');

        $data = [
            'uuid' => $user->uuid,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
            'status' => $user->status,
            'createdAt' => $user->created_at,
            'updatedAt' => $user->updated_at,
        ];

        if ($user->subscription) {
            $data['subscription'] = [
                'plan' => $user->subscription->plan,
                'status' => $user->subscription->status,
                'expiresAt' => $user->subscription->expires_at,
            ];
        }

        return $data;
    }
}
