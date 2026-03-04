<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Settings;
use App\Models\SubscriptionPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== SETTINGS ====================
        Settings::firstOrCreate([], [
            'app_name' => 'Islamic Learning Platform',
            'support_email' => 'support@ilp.com',
            'contact_phone' => '+1-800-ILP-LEARN',
            'website' => 'https://ilp.com',
            'payment_provider' => 'manual',
            'payment_currency' => 'USD',
            'smtp_port' => 587,
        ]);

        // ==================== USERS ====================
        $admin = User::firstOrCreate(
            ['email' => 'admin@ilp.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $mentor1 = User::firstOrCreate(
            ['email' => 'sheikh.ahmad@ilp.com'],
            [
                'name' => 'Sheikh Ahmad',
                'password' => Hash::make('mentor123'),
                'role' => 'mentor',
                'status' => 'active',
            ]
        );

        $mentor2 = User::firstOrCreate(
            ['email' => 'ustadha.fatima@ilp.com'],
            [
                'name' => 'Ustadha Fatima',
                'password' => Hash::make('mentor123'),
                'role' => 'mentor',
                'status' => 'active',
            ]
        );

        $students = [];
        $studentData = [
            ['email' => 'ali@student.com', 'name' => 'Ali Hassan'],
            ['email' => 'sara@student.com', 'name' => 'Sara Mohammed'],
            ['email' => 'omar@student.com', 'name' => 'Omar Abdullah'],
            ['email' => 'aisha@student.com', 'name' => 'Aisha Rahman'],
            ['email' => 'yusuf@student.com', 'name' => 'Yusuf Ibrahim'],
        ];

        foreach ($studentData as $data) {
            $students[] = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('student123'),
                    'role' => 'student',
                    'status' => 'active',
                ]
            );
        }

        // ==================== CATEGORIES ====================
        $categories = [];
        $categoryData = [
            ['name' => 'Quran', 'icon' => '📖'],
            ['name' => 'Tajweed', 'icon' => '🎯'],
            ['name' => 'Hadith', 'icon' => '📜'],
            ['name' => 'Fiqh', 'icon' => '⚖️'],
            ['name' => 'Seerah', 'icon' => '🕌'],
            ['name' => 'Aqeedah', 'icon' => '💡'],
        ];

        foreach ($categoryData as $data) {
            $categories[$data['name']] = Category::firstOrCreate(['name' => $data['name']], $data);
        }

        // ==================== SUBSCRIPTION PLANS ====================
        $planBasic = SubscriptionPlan::firstOrCreate(
            ['name' => 'Basic'],
            [
                'price' => 9.99,
                'interval' => 'monthly',
                'features' => ['Access to 5 courses', 'Basic support', 'Mobile app access'],
                'max_courses' => 5,
                'status' => 'active',
            ]
        );

        $planPremium = SubscriptionPlan::firstOrCreate(
            ['name' => 'Premium'],
            [
                'price' => 19.99,
                'interval' => 'monthly',
                'features' => ['Unlimited courses', 'Priority support', 'Mobile app', 'Download content', 'Certificate of completion'],
                'max_courses' => null,
                'status' => 'active',
            ]
        );

        $planAnnual = SubscriptionPlan::firstOrCreate(
            ['name' => 'Annual'],
            [
                'price' => 149.99,
                'interval' => 'yearly',
                'features' => ['Unlimited courses', '1-on-1 mentoring (2 sessions/month)', 'Priority support', 'All premium features', 'Early access to new content'],
                'max_courses' => null,
                'status' => 'active',
            ]
        );

        // ==================== COURSES ====================
        // Course 1: Quran Recitation
        $course1 = Course::firstOrCreate(
            ['title' => 'Introduction to Quran Recitation'],
            [
                'description' => 'Learn the basics of Quran recitation with proper pronunciation and Tajweed rules.',
                'category_id' => $categories['Quran']->id,
                'instructor_id' => $mentor1->id,
                'status' => 'published',
                'level' => 'beginner',
                'duration' => 1200,
                'enrollment_count' => 245,
            ]
        );

        if ($course1->modules()->count() === 0) {
            $m1 = Module::create(['title' => 'Arabic Alphabet', 'course_id' => $course1->id, 'order' => 1, 'description' => 'Master the Arabic alphabet']);
            Lesson::create(['title' => 'Introduction to Arabic Letters', 'module_id' => $m1->id, 'order' => 1, 'type' => 'video', 'duration' => 15]);
            Lesson::create(['title' => 'Vowel Signs (Harakat)', 'module_id' => $m1->id, 'order' => 2, 'type' => 'video', 'duration' => 20]);
            Lesson::create(['title' => 'Practice Quiz', 'module_id' => $m1->id, 'order' => 3, 'type' => 'quiz', 'duration' => 10]);

            $m2 = Module::create(['title' => 'Basic Recitation', 'course_id' => $course1->id, 'order' => 2, 'description' => 'Start reciting short Surahs']);
            Lesson::create(['title' => 'Surah Al-Fatihah', 'module_id' => $m2->id, 'order' => 1, 'type' => 'video', 'duration' => 25]);
            Lesson::create(['title' => 'Surah Al-Ikhlas', 'module_id' => $m2->id, 'order' => 2, 'type' => 'video', 'duration' => 20]);
            Lesson::create(['title' => 'Flashcard Review', 'module_id' => $m2->id, 'order' => 3, 'type' => 'flashcard', 'duration' => 15]);

            $m3 = Module::create(['title' => 'Tajweed Fundamentals', 'course_id' => $course1->id, 'order' => 3, 'description' => 'Learn basic Tajweed rules']);
            Lesson::create(['title' => 'Noon Sakinah Rules', 'module_id' => $m3->id, 'order' => 1, 'type' => 'video', 'duration' => 30]);
            Lesson::create(['title' => 'Meem Sakinah Rules', 'module_id' => $m3->id, 'order' => 2, 'type' => 'reading', 'duration' => 20]);
        }

        // Course 2: Tajweed Mastery
        $course2 = Course::firstOrCreate(
            ['title' => 'Tajweed Rules Mastery'],
            [
                'description' => 'Comprehensive guide to Tajweed rules for beautiful Quran recitation.',
                'category_id' => $categories['Tajweed']->id,
                'instructor_id' => $mentor1->id,
                'status' => 'published',
                'level' => 'intermediate',
                'duration' => 1800,
                'enrollment_count' => 189,
            ]
        );

        if ($course2->modules()->count() === 0) {
            $m1 = Module::create(['title' => 'Makharij Al-Huruf', 'course_id' => $course2->id, 'order' => 1, 'description' => 'Points of articulation']);
            Lesson::create(['title' => 'Throat Letters', 'module_id' => $m1->id, 'order' => 1, 'type' => 'video', 'duration' => 35]);
            Lesson::create(['title' => 'Tongue Letters', 'module_id' => $m1->id, 'order' => 2, 'type' => 'video', 'duration' => 40]);
            Lesson::create(['title' => 'Lip Letters', 'module_id' => $m1->id, 'order' => 3, 'type' => 'video', 'duration' => 25]);

            $m2 = Module::create(['title' => 'Sifat Al-Huruf', 'course_id' => $course2->id, 'order' => 2, 'description' => 'Characteristics of letters']);
            Lesson::create(['title' => 'Heavy and Light Letters', 'module_id' => $m2->id, 'order' => 1, 'type' => 'video', 'duration' => 30]);
            Lesson::create(['title' => 'Madd Rules', 'module_id' => $m2->id, 'order' => 2, 'type' => 'video', 'duration' => 45]);
            Lesson::create(['title' => 'Assessment Quiz', 'module_id' => $m2->id, 'order' => 3, 'type' => 'quiz', 'duration' => 20]);
        }

        // Course 3: Hadith Sciences
        $course3 = Course::firstOrCreate(
            ['title' => 'Hadith Sciences Foundation'],
            [
                'description' => 'An introduction to the science of Hadith, its classification and methodology.',
                'category_id' => $categories['Hadith']->id,
                'instructor_id' => $mentor2->id,
                'status' => 'published',
                'level' => 'intermediate',
                'duration' => 1500,
                'enrollment_count' => 134,
            ]
        );

        if ($course3->modules()->count() === 0) {
            $m1 = Module::create(['title' => 'Introduction to Hadith', 'course_id' => $course3->id, 'order' => 1, 'description' => 'Basics of Hadith science']);
            Lesson::create(['title' => 'What is Hadith?', 'module_id' => $m1->id, 'order' => 1, 'type' => 'video', 'duration' => 20]);
            Lesson::create(['title' => 'Types of Hadith', 'module_id' => $m1->id, 'order' => 2, 'type' => 'reading', 'duration' => 25]);
            Lesson::create(['title' => 'The Sanad', 'module_id' => $m1->id, 'order' => 3, 'type' => 'video', 'duration' => 30]);

            $m2 = Module::create(['title' => 'Hadith Classification', 'course_id' => $course3->id, 'order' => 2, 'description' => 'How Hadith are classified']);
            Lesson::create(['title' => 'Sahih and Hasan Hadith', 'module_id' => $m2->id, 'order' => 1, 'type' => 'video', 'duration' => 35]);
            Lesson::create(['title' => 'Weak and Fabricated Hadith', 'module_id' => $m2->id, 'order' => 2, 'type' => 'video', 'duration' => 30]);
        }

        // Course 4: Fiqh of Salah
        $course4 = Course::firstOrCreate(
            ['title' => 'Fiqh of Salah'],
            [
                'description' => 'Learn the rulings and conditions of prayer according to Islamic jurisprudence.',
                'category_id' => $categories['Fiqh']->id,
                'instructor_id' => $mentor2->id,
                'status' => 'published',
                'level' => 'beginner',
                'duration' => 900,
                'enrollment_count' => 312,
            ]
        );

        if ($course4->modules()->count() === 0) {
            $m1 = Module::create(['title' => 'Conditions of Prayer', 'course_id' => $course4->id, 'order' => 1, 'description' => 'Prerequisites for valid prayer']);
            Lesson::create(['title' => 'Purity and Wudu', 'module_id' => $m1->id, 'order' => 1, 'type' => 'video', 'duration' => 25]);
            Lesson::create(['title' => 'Dress Code in Prayer', 'module_id' => $m1->id, 'order' => 2, 'type' => 'reading', 'duration' => 15]);
            Lesson::create(['title' => 'Direction of Prayer (Qibla)', 'module_id' => $m1->id, 'order' => 3, 'type' => 'video', 'duration' => 20]);

            $m2 = Module::create(['title' => 'Pillars of Prayer', 'course_id' => $course4->id, 'order' => 2, 'description' => 'The essential acts of prayer']);
            Lesson::create(['title' => 'Takbir and Opening', 'module_id' => $m2->id, 'order' => 1, 'type' => 'video', 'duration' => 20]);
            Lesson::create(['title' => 'Ruku and Sujud', 'module_id' => $m2->id, 'order' => 2, 'type' => 'video', 'duration' => 25]);
            Lesson::create(['title' => 'Tashahhud and Tasleem', 'module_id' => $m2->id, 'order' => 3, 'type' => 'video', 'duration' => 20]);

            $m3 = Module::create(['title' => 'Common Mistakes', 'course_id' => $course4->id, 'order' => 3, 'description' => 'Avoid common errors in prayer']);
            Lesson::create(['title' => 'Mistakes that Invalidate Prayer', 'module_id' => $m3->id, 'order' => 1, 'type' => 'reading', 'duration' => 20]);
        }

        // ==================== TRANSACTIONS ====================
        $txData = [
            ['user' => $students[0], 'plan' => $planPremium, 'amount' => 19.99, 'method' => 'stripe', 'status' => 'completed'],
            ['user' => $students[1], 'plan' => $planBasic, 'amount' => 9.99, 'method' => 'paypal', 'status' => 'completed'],
            ['user' => $students[2], 'plan' => $planAnnual, 'amount' => 149.99, 'method' => 'bank_transfer', 'status' => 'pending'],
            ['user' => $students[3], 'plan' => $planPremium, 'amount' => 19.99, 'method' => 'razorpay', 'status' => 'completed'],
            ['user' => $students[4], 'plan' => $planBasic, 'amount' => 9.99, 'method' => 'manual', 'status' => 'failed'],
            ['user' => $students[0], 'plan' => $planAnnual, 'amount' => 149.99, 'method' => 'stripe', 'status' => 'refunded'],
        ];

        foreach ($txData as $i => $data) {
            $txId = 'TXN-' . (time() - $i * 86400) . '-' . strtoupper(substr(md5($i), 0, 6));
            Transaction::firstOrCreate(
                ['user_id' => $data['user']->id, 'plan_id' => $data['plan']->id, 'amount' => $data['amount']],
                [
                    'transaction_id' => $txId,
                    'user_name' => $data['user']->name,
                    'user_email' => $data['user']->email,
                    'plan_name' => $data['plan']->name,
                    'currency' => 'USD',
                    'payment_method' => $data['method'],
                    'status' => $data['status'],
                ]
            );
        }

        // ==================== ENROLLMENTS ====================
        $enrollmentData = [
            [$students[0], $course1], [$students[0], $course2],
            [$students[1], $course1], [$students[1], $course4],
            [$students[2], $course2], [$students[2], $course3],
            [$students[3], $course3], [$students[3], $course4],
            [$students[4], $course1], [$students[4], $course4],
        ];
        foreach ($enrollmentData as [$student, $course]) {
            \Illuminate\Support\Facades\DB::table('user_enrolled_courses')
                ->insertOrIgnore([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
        }

        // ==================== LESSON COMPLETIONS ====================
        foreach ($enrollmentData as [$student, $course]) {
            $progress = UserProgress::firstOrCreate(
                ['user_id' => $student->id, 'course_id' => $course->id],
                ['percentage' => rand(10, 90)]
            );
            $lessons = Lesson::whereHas('module', fn($q) => $q->where('course_id', $course->id))
                ->take(2)->get();
            foreach ($lessons as $lesson) {
                \Illuminate\Support\Facades\DB::table('user_completed_lessons')
                    ->insertOrIgnore([
                        'user_progress_id' => $progress->id,
                        'lesson_id' => $lesson->id,
                        'created_at' => now()->subDays(rand(1, 20)),
                        'updated_at' => now(),
                    ]);
            }
        }

        // ==================== HOMEWORK & SUBMISSIONS ====================
        $allLessons = Lesson::all();
        $hw1 = Homework::firstOrCreate(
            ['title' => 'Tajweed Practice: Noon Sakinah'],
            [
                'lesson_id' => $allLessons->where('title', 'Noon Sakinah Rules')->first()?->id ?? $allLessons->first()->id,
                'course_id' => $course1->id,
                'instructions' => 'Record yourself reciting 5 verses applying Noon Sakinah rules.',
                'submission_type' => 'audio',
                'max_score' => 100,
                'status' => 'published',
            ]
        );
        $hw2 = Homework::firstOrCreate(
            ['title' => 'Makharij Identification Exercise'],
            [
                'lesson_id' => $allLessons->where('title', 'Throat Letters')->first()?->id ?? $allLessons->first()->id,
                'course_id' => $course2->id,
                'instructions' => 'Identify the correct makhraj for 20 Arabic letters.',
                'submission_type' => 'text',
                'max_score' => 20,
                'status' => 'published',
            ]
        );
        $hw3 = Homework::firstOrCreate(
            ['title' => 'Wudu Step-by-Step Description'],
            [
                'lesson_id' => $allLessons->where('title', 'Purity and Wudu')->first()?->id ?? $allLessons->first()->id,
                'course_id' => $course4->id,
                'instructions' => 'Write a detailed description of the steps and conditions of Wudu.',
                'submission_type' => 'text',
                'max_score' => 50,
                'status' => 'published',
            ]
        );

        $submissionData = [
            [$students[0], $hw1, 'reviewed',  $mentor1->id, $mentor1->name, now()->subDays(5)],
            [$students[1], $hw1, 'reviewed',  $mentor1->id, $mentor1->name, now()->subDays(4)],
            [$students[2], $hw2, 'reviewed',  $mentor1->id, $mentor1->name, now()->subDays(3)],
            [$students[3], $hw3, 'reviewed',  $mentor2->id, $mentor2->name, now()->subDays(2)],
            [$students[4], $hw3, 'pending',   null,         null,           now()->subDays(1)],
            [$students[0], $hw2, 'pending',   null,         null,           now()->subHours(6)],
        ];
        foreach ($submissionData as [$student, $hw, $status, $mentorId, $mentorName, $reviewedAt]) {
            HomeworkSubmission::firstOrCreate(
                ['homework_id' => $hw->id, 'student_id' => $student->id],
                [
                    'student_name'       => $student->name,
                    'student_email'      => $student->email,
                    'content'            => 'Submission content for ' . $hw->title,
                    'assigned_mentor_id' => $mentorId,
                    'mentor_name'        => $mentorName,
                    'status'             => $status,
                    'grade'              => $status === 'reviewed' ? rand(70, 100) : null,
                    'feedback'           => $status === 'reviewed' ? 'Good effort, keep practicing.' : null,
                    'submitted_at'       => now()->subDays(rand(6, 14)),
                    'reviewed_at'        => $status === 'reviewed' ? $reviewedAt : null,
                ]
            );
        }

        // ==================== CERTIFICATES ====================
        $certData = [
            [$students[0], $course1, 'Introduction to Quran Recitation', now()->subDays(10)],
            [$students[1], $course4, 'Fiqh of Salah',                    now()->subDays(7)],
            [$students[2], $course2, 'Tajweed Rules Mastery',             now()->subDays(4)],
            [$students[3], $course3, 'Hadith Sciences Foundation',        now()->subDays(2)],
        ];
        foreach ($certData as [$student, $course, $courseName, $issueDate]) {
            Certificate::firstOrCreate(
                ['student_id' => $student->id, 'course_id' => $course->id],
                [
                    'certificate_id' => 'CERT-' . strtoupper(substr(md5($student->id . $course->id), 0, 8)),
                    'student_name'   => $student->name,
                    'student_email'  => $student->email,
                    'course_name'    => $courseName,
                    'issue_date'     => $issueDate,
                    'status'         => 'issued',
                ]
            );
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@ilp.com / admin123');
        $this->command->info('Mentors: sheikh.ahmad@ilp.com / mentor123, ustadha.fatima@ilp.com / mentor123');
        $this->command->info('Students: ali@student.com ... yusuf@student.com / student123');
    }
}
