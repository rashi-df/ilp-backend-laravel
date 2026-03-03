<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // ==================== PLANS ====================

    public function listPlans(Request $request): JsonResponse
    {
        $query = SubscriptionPlan::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $plans = $query->orderBy('price')->get();

        return response()->json(['success' => true, 'data' => $plans]);
    }

    public function createPlan(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:subscription_plans,name',
            'price' => 'required|numeric|min:0',
            'interval' => 'required|in:monthly,yearly',
            'features' => 'sometimes|array',
            'maxCourses' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $request->name,
            'price' => $request->price,
            'interval' => $request->interval,
            'features' => $request->get('features', []),
            'max_courses' => $request->maxCourses,
            'status' => $request->get('status', 'active'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan created successfully',
            'data' => $plan,
        ], 201);
    }

    public function updatePlan(Request $request, string $uuid): JsonResponse
    {
        $plan = SubscriptionPlan::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'sometimes|string|unique:subscription_plans,name,' . $plan->id,
            'price' => 'sometimes|numeric|min:0',
            'interval' => 'sometimes|in:monthly,yearly',
            'features' => 'sometimes|array',
            'maxCourses' => 'nullable|integer|min:0',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $data = $request->only(['name', 'price', 'interval', 'status']);
        if ($request->has('features')) {
            $data['features'] = $request->features;
        }
        if ($request->has('maxCourses')) {
            $data['max_courses'] = $request->maxCourses;
        }

        $plan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Plan updated successfully',
            'data' => $plan,
        ]);
    }

    public function togglePlanStatus(string $uuid): JsonResponse
    {
        $plan = SubscriptionPlan::where('uuid', $uuid)->firstOrFail();
        $plan->update(['status' => $plan->status === 'active' ? 'inactive' : 'active']);

        return response()->json([
            'success' => true,
            'message' => "Plan {$plan->status}",
            'data' => ['status' => $plan->status],
        ]);
    }

    // ==================== TRANSACTIONS ====================

    public function listTransactions(Request $request): JsonResponse
    {
        $query = Transaction::with('user', 'plan');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->paymentMethod) {
            $query->where('payment_method', $request->paymentMethod);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('user_name', 'ilike', "%{$request->search}%")
                  ->orWhere('user_email', 'ilike', "%{$request->search}%")
                  ->orWhere('transaction_id', 'ilike', "%{$request->search}%");
            });
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = min(100, max(1, (int) $request->get('limit', 20)));
        $total = $query->count();
        $transactions = $query->orderBy('created_at', 'desc')
                               ->skip(($page - 1) * $limit)
                               ->take($limit)
                               ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function showTransaction(string $uuid): JsonResponse
    {
        $transaction = Transaction::with('user', 'plan')->where('uuid', $uuid)->firstOrFail();

        return response()->json(['success' => true, 'data' => $transaction]);
    }

    public function createTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'userUuid' => 'nullable|string|exists:users,uuid',
            'planUuid' => 'nullable|string|exists:subscription_plans,uuid',
            'amount' => 'required|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'paymentMethod' => 'sometimes|in:stripe,razorpay,paypal,bank_transfer,manual',
            'proofUrl' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $user = $request->userUuid
            ? \App\Models\User::where('uuid', $request->userUuid)->first()
            : null;
        $plan = $request->planUuid
            ? SubscriptionPlan::where('uuid', $request->planUuid)->first()
            : null;

        $transactionId = 'TXN-' . time() . '-' . strtoupper(substr(md5(rand()), 0, 6));

        $transaction = Transaction::create([
            'transaction_id' => $transactionId,
            'user_id' => $user?->id,
            'plan_id' => $plan?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'plan_name' => $plan?->name,
            'amount' => $request->amount,
            'currency' => $request->get('currency', 'USD'),
            'payment_method' => $request->get('paymentMethod', 'manual'),
            'proof_url' => $request->proofUrl,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load('user', 'plan'),
        ], 201);
    }

    public function approveTransaction(string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->firstOrFail();

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transactions can be approved',
            ], 400);
        }

        $transaction->update(['status' => 'completed']);

        return response()->json([
            'success' => true,
            'message' => 'Transaction approved',
            'data' => $transaction,
        ]);
    }

    public function rejectTransaction(Request $request, string $uuid): JsonResponse
    {
        $transaction = Transaction::where('uuid', $uuid)->firstOrFail();

        if ($transaction->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending transactions can be rejected',
            ], 400);
        }

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $transaction->update([
            'status' => 'failed',
            'notes' => $request->notes ?? $transaction->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction rejected',
            'data' => $transaction,
        ]);
    }

    public function exportTransactionsCsv(Request $request)
    {
        $query = Transaction::with('user', 'plan');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="transactions.csv"',
        ];

        $callback = function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Transaction ID', 'User', 'Email', 'Plan', 'Amount', 'Currency', 'Method', 'Status', 'Date']);
            foreach ($transactions as $t) {
                fputcsv($handle, [
                    $t->transaction_id,
                    $t->user_name,
                    $t->user_email,
                    $t->plan_name,
                    $t->amount,
                    $t->currency,
                    $t->payment_method,
                    $t->status,
                    $t->created_at->toISOString(),
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
