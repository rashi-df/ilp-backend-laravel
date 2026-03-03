<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeworkSubmission;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalStudents = User::where('role', 'student')->count();

        $currentMonthRevenue = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $lastMonthRevenue = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        $revenueTrend = $lastMonthRevenue > 0
            ? round((($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $pendingReviews = HomeworkSubmission::where('status', 'pending')->count();

        $avgCompletion = UserProgress::avg('percentage') ?? 0;

        return response()->json([
            'totalStudents' => [
                'value' => $totalStudents,
                'trend' => 0,
                'trendUp' => true,
            ],
            'monthlyRevenue' => [
                'value' => round((float) $currentMonthRevenue, 2),
                'trend' => $revenueTrend,
                'trendUp' => $revenueTrend >= 0,
            ],
            'pendingReviews' => [
                'value' => $pendingReviews,
                'trend' => 0,
                'trendUp' => false,
            ],
            'completionRate' => [
                'value' => round((float) $avgCompletion, 1),
                'trend' => 0,
                'trendUp' => true,
            ],
        ]);
    }

    public function recentEnrollments(): JsonResponse
    {
        $rows = [];

        User::where('role', 'student')
            ->with(['enrolledCourses' => function ($q) {
                $q->latest('user_enrolled_courses.created_at');
            }])
            ->latest()
            ->take(10)
            ->get()
            ->each(function ($student) use (&$rows) {
                if ($student->enrolledCourses->isEmpty()) {
                    $rows[] = [
                        'studentName' => $student->name,
                        'email' => $student->email,
                        'courseName' => null,
                        'enrolledAt' => $student->created_at,
                    ];
                } else {
                    foreach ($student->enrolledCourses->take(2) as $course) {
                        $rows[] = [
                            'studentName' => $student->name,
                            'email' => $student->email,
                            'courseName' => $course->title,
                            'enrolledAt' => $student->created_at,
                        ];
                    }
                }
            });

        return response()->json(array_slice($rows, 0, 10));
    }

    public function recentActivity(): JsonResponse
    {
        $activities = [];

        // Recent transactions
        $transactions = Transaction::with('user')
            ->latest()
            ->take(5)
            ->get();

        foreach ($transactions as $t) {
            $activities[] = [
                'type' => 'payment',
                'message' => "{$t->user_name} made a payment of {$t->amount} {$t->currency}",
                'createdAt' => $t->created_at,
            ];
        }

        // Recent submissions
        $submissions = HomeworkSubmission::with('student', 'homework')
            ->latest('submitted_at')
            ->take(5)
            ->get();

        foreach ($submissions as $s) {
            $activities[] = [
                'type' => 'submission',
                'message' => "{$s->student->name} submitted homework",
                'createdAt' => $s->submitted_at,
            ];
        }

        // Sort by date
        usort($activities, fn($a, $b) => strcmp((string) $b['createdAt'], (string) $a['createdAt']));

        return response()->json(array_slice($activities, 0, 10));
    }
}
