<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('subscription.plan');

        if ($request->role) {
            $query->where('role', $request->role);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'ilike', "%{$request->search}%")
                  ->orWhere('email', 'ilike', "%{$request->search}%");
            });
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $users = $query->orderBy('created_at', 'desc')
                       ->skip(($page - 1) * $limit)
                       ->take($limit)
                       ->get();

        return response()->json([
            'success' => true,
            'data' => $users->map(fn($u) => $this->formatUser($u)),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $user = User::with('subscription.plan', 'enrolledCourses', 'progress.course')
            ->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->formatUser($user),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'sometimes|in:admin,mentor,student',
            'avatar' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->get('role', 'student'),
            'avatar' => $request->avatar,
            'phone' => $request->phone,
            'status' => $request->get('status', 'active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $this->formatUser($user),
        ], 201);
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'role' => 'sometimes|in:admin,mentor,student',
            'avatar' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $data = $request->only(['name', 'email', 'role', 'avatar', 'phone', 'status']);
        if ($request->has('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    public function toggleStatus(string $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);

        return response()->json([
            'success' => true,
            'message' => "User {$user->status}",
            'data' => ['status' => $user->status],
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = User::query();

        if ($request->role) {
            $query->where('role', $request->role);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
        ];

        $callback = function () use ($users) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Role', 'Status', 'Phone', 'Created At']);
            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->status,
                    $user->phone,
                    $user->created_at->toISOString(),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function formatUser(User $user): array
    {
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

        if ($user->relationLoaded('subscription') && $user->subscription) {
            $data['subscription'] = [
                'plan' => $user->subscription->plan,
                'status' => $user->subscription->status,
                'expiresAt' => $user->subscription->expires_at,
            ];
        }

        if ($user->relationLoaded('enrolledCourses')) {
            $data['enrolledCourses'] = $user->enrolledCourses->pluck('uuid');
        }

        return $data;
    }
}
