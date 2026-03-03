<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\UserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // GET /api/student/courses
    public function listCourses(Request $request): JsonResponse
    {
        $query = Course::with('category', 'instructor')
            ->where('status', 'published');

        if ($request->search) {
            $query->where('title', 'ilike', "%{$request->search}%");
        }

        $courses = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $courses->map(fn($c) => $this->formatCourse($c)),
        ]);
    }

    // GET /api/student/courses/{uuid}
    public function showCourse(string $uuid): JsonResponse
    {
        $course = Course::with([
            'category',
            'instructor',
            'modules.lessons.video',
            'modules.lessons.quiz',
            'modules.lessons.flashcardSet',
        ])->where('uuid', $uuid)->where('status', 'published')->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->formatCourse($course, true),
        ]);
    }

    // GET /api/student/lessons/{uuid}
    public function showLesson(string $uuid): JsonResponse
    {
        $lesson = Lesson::with('video', 'quiz', 'flashcardSet')
            ->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $this->formatLesson($lesson),
        ]);
    }

    // GET /api/student/activities/quizzes/{uuid}
    public function getQuiz(string $uuid): JsonResponse
    {
        $quiz = Quiz::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $quiz->uuid,
                'title' => $quiz->title,
                'questions' => $quiz->questions,
                'passingScore' => $quiz->passing_score,
            ],
        ]);
    }

    // GET /api/student/activities/flashcards/{uuid}
    public function getFlashcardSet(string $uuid): JsonResponse
    {
        $set = FlashcardSet::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'uuid' => $set->uuid,
                'title' => $set->title,
                'cards' => $set->cards,
            ],
        ]);
    }

    // GET /api/student/progress
    public function getProgress(Request $request): JsonResponse
    {
        $user = $request->user();

        $progressRecords = UserProgress::with([
            'course.modules.lessons',
            'completedLessons',
        ])->where('user_id', $user->id)->get();

        $courseProgress = $progressRecords->map(function ($progress) {
            $totalLessons = $progress->course->modules->sum(fn($m) => $m->lessons->count());
            $completedCount = $progress->completedLessons->count();
            $percent = $totalLessons > 0 ? round($completedCount / $totalLessons, 4) : 0.0;

            return [
                'courseId' => $progress->course->uuid,
                'courseTitle' => $progress->course->title,
                'courseIcon' => $progress->course->thumbnail ?? '📖',
                'progressPercent' => $percent,
                'completedLessons' => $completedCount,
                'totalLessons' => $totalLessons,
            ];
        });

        $completed = $courseProgress->filter(fn($cp) => $cp['progressPercent'] >= 1.0)->count();
        $inProgress = $courseProgress->filter(fn($cp) => $cp['progressPercent'] > 0 && $cp['progressPercent'] < 1.0)->count();
        $overallProgress = $courseProgress->isNotEmpty()
            ? round($courseProgress->avg('progressPercent'), 4)
            : 0.0;

        return response()->json([
            'success' => true,
            'data' => [
                'overallProgress' => $overallProgress,
                'coursesCompleted' => $completed,
                'coursesInProgress' => $inProgress,
                'courseProgress' => $courseProgress->values(),
            ],
        ]);
    }

    // POST /api/student/lessons/{uuid}/complete
    public function completeLesson(string $uuid, Request $request): JsonResponse
    {
        $user = $request->user();
        $lesson = Lesson::with('module.course')->where('uuid', $uuid)->firstOrFail();
        $course = $lesson->module->course;

        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['percentage' => 0]
        );

        if (!$progress->completedLessons()->where('lesson_id', $lesson->id)->exists()) {
            $progress->completedLessons()->attach($lesson->id);

            $totalLessons = $course->modules()->withCount('lessons')->get()->sum('lessons_count');
            $completedCount = $progress->completedLessons()->count();
            $percentage = $totalLessons > 0 ? round($completedCount / $totalLessons, 4) : 0.0;
            $progress->update(['percentage' => $percentage]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as complete',
            'data' => ['percentage' => $progress->fresh()->percentage],
        ]);
    }

    private function formatCourse(Course $course, bool $withModules = false): array
    {
        $data = [
            'uuid' => $course->uuid,
            'title' => $course->title,
            'description' => $course->description,
            'thumbnail' => $course->thumbnail,
            'level' => $course->level,
            'duration' => $course->duration,
            'enrollmentCount' => $course->enrollment_count,
        ];

        if ($course->relationLoaded('category') && $course->category) {
            $data['category'] = [
                'uuid' => $course->category->uuid,
                'name' => $course->category->name,
                'icon' => $course->category->icon,
            ];
        }

        if ($course->relationLoaded('instructor') && $course->instructor) {
            $data['instructor'] = [
                'uuid' => $course->instructor->uuid,
                'name' => $course->instructor->name,
            ];
        }

        if ($withModules && $course->relationLoaded('modules')) {
            $data['modules'] = $course->modules->map(function ($module) {
                return [
                    'uuid' => $module->uuid,
                    'title' => $module->title,
                    'order' => $module->order,
                    'lessons' => $module->lessons->map(fn($l) => $this->formatLesson($l)),
                ];
            });
        }

        return $data;
    }

    private function formatLesson(Lesson $lesson): array
    {
        $data = [
            'uuid' => $lesson->uuid,
            'title' => $lesson->title,
            'type' => $lesson->type,
            'duration' => $lesson->duration,
            'content' => $lesson->content,
        ];

        if ($lesson->relationLoaded('video') && $lesson->video) {
            $data['videoUuid'] = $lesson->video->uuid;
            $data['embedUrl'] = $lesson->video->embed_url;
        }

        if ($lesson->relationLoaded('quiz') && $lesson->quiz) {
            $data['quizUuid'] = $lesson->quiz->uuid;
        }

        if ($lesson->relationLoaded('flashcardSet') && $lesson->flashcardSet) {
            $data['flashcardSetUuid'] = $lesson->flashcardSet->uuid;
        }

        return $data;
    }
}
