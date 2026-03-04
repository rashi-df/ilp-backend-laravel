<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\HomeworkSubmission;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
                        'id'          => $student->uuid . '-nocourse',
                        'studentName' => $student->name,
                        'email'       => $student->email,
                        'courseName'  => null,
                        'enrolledAt'  => $student->created_at,
                    ];
                } else {
                    foreach ($student->enrolledCourses->take(2) as $course) {
                        $rows[] = [
                            'id'          => $student->uuid . '-' . $course->uuid,
                            'studentName' => $student->name,
                            'email'       => $student->email,
                            'courseName'  => $course->title,
                            'enrolledAt'  => $student->created_at,
                        ];
                    }
                }
            });

        return response()->json(array_slice($rows, 0, 10));
    }

    public function recentActivity(): JsonResponse
    {
        $activities = [];

        // Payments
        $transactions = Transaction::with('user')->latest()->take(4)->get();
        foreach ($transactions as $t) {
            $activities[] = [
                'id'        => 'payment-' . $t->uuid,
                'type'      => 'payment',
                'message'   => "{$t->user_name} subscribed to {$t->plan_name} plan",
                'createdAt' => $t->created_at,
            ];
        }

        // Homework submissions
        $submissions = HomeworkSubmission::with('student', 'homework')
            ->latest('submitted_at')
            ->take(4)
            ->get();
        foreach ($submissions as $s) {
            $title = $s->homework->title ?? 'homework';
            $activities[] = [
                'id'        => 'submission-' . $s->uuid,
                'type'      => 'submission',
                'message'   => "{$s->student->name} submitted homework on \"{$title}\"",
                'createdAt' => $s->submitted_at,
            ];
        }

        // Lesson completions
        $completions = DB::table('user_completed_lessons')
            ->join('user_progress', 'user_completed_lessons.user_progress_id', '=', 'user_progress.id')
            ->join('users', 'user_progress.user_id', '=', 'users.id')
            ->join('lessons', 'user_completed_lessons.lesson_id', '=', 'lessons.id')
            ->join('modules', 'lessons.module_id', '=', 'modules.id')
            ->join('courses', 'modules.course_id', '=', 'courses.id')
            ->select(
                'users.name as student_name',
                'lessons.title as lesson_title',
                'courses.title as course_title',
                'user_completed_lessons.created_at',
                'user_completed_lessons.user_progress_id',
                'user_completed_lessons.lesson_id'
            )
            ->orderByDesc('user_completed_lessons.created_at')
            ->take(4)
            ->get();
        foreach ($completions as $c) {
            $activities[] = [
                'id'        => 'course-' . $c->user_progress_id . '-' . $c->lesson_id,
                'type'      => 'course',
                'message'   => "{$c->student_name} completed \"{$c->lesson_title}\" in {$c->course_title}",
                'createdAt' => $c->created_at,
            ];
        }

        // Course enrollments
        $enrollments = DB::table('user_enrolled_courses')
            ->join('users', 'user_enrolled_courses.user_id', '=', 'users.id')
            ->join('courses', 'user_enrolled_courses.course_id', '=', 'courses.id')
            ->select(
                'users.name as student_name',
                'courses.title as course_title',
                'user_enrolled_courses.created_at',
                'user_enrolled_courses.user_id',
                'user_enrolled_courses.course_id'
            )
            ->orderByDesc('user_enrolled_courses.created_at')
            ->take(4)
            ->get();
        foreach ($enrollments as $e) {
            $activities[] = [
                'id'        => 'enrollment-' . $e->user_id . '-' . $e->course_id,
                'type'      => 'enrollment',
                'message'   => "{$e->student_name} enrolled in {$e->course_title}",
                'createdAt' => $e->created_at,
            ];
        }

        // Certificates earned
        $certificates = Certificate::where('status', 'issued')
            ->latest('issue_date')
            ->take(4)
            ->get();
        foreach ($certificates as $cert) {
            $activities[] = [
                'id'        => 'certificate-' . $cert->uuid,
                'type'      => 'certificate',
                'message'   => "{$cert->student_name} earned a certificate for {$cert->course_name}",
                'createdAt' => $cert->issue_date,
            ];
        }

        // Homework reviewed
        $reviewed = HomeworkSubmission::with('homework')
            ->whereNotNull('reviewed_at')
            ->latest('reviewed_at')
            ->take(4)
            ->get();
        foreach ($reviewed as $r) {
            $mentorName  = $r->mentor_name  ?? 'A mentor';
            $studentName = $r->student_name ?? 'a student';
            $title       = $r->homework->title ?? 'homework';
            $activities[] = [
                'id'        => 'review-' . $r->uuid,
                'type'      => 'review',
                'message'   => "{$mentorName} reviewed {$studentName}'s homework on \"{$title}\"",
                'createdAt' => $r->reviewed_at,
            ];
        }

        // Sort descending by timestamp, return top 10
        usort($activities, fn($a, $b) => strcmp((string) $b['createdAt'], (string) $a['createdAt']));

        return response()->json(array_slice($activities, 0, 10));
    }
}
